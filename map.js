#!/usr/bin/env node
/**
 * Step 2 — Widget → Schema Mapper — v2
 * ------------------------------------
 * Changes vs v1:
 *  1. SECTION SCOPING: lists and images are attached to the h2 section they
 *     appear under (instead of page-level pools). Hero zone = everything
 *     before the first h2 → hero lists/images stay separate.
 *  2. REVIEW GROUPING: inside the reviews section ("Wat zeggen onze
 *     klanten"), your sites build each review as
 *        text-editor (review text) → heading[p] (name) → heading[p] (location)
 *     These triples are detected and grouped into reviews[].
 *  3. star-rating → rating value; "Waardering X van Y" / "95% Beveelt..."
 *     paragraph headings → rating_text.
 *
 * Usage:
 *   node map.js <extracted.json> [site-content.json] [--site=domain.nl]
 *
 * Also usable as a library (no file I/O):
 *   const { mapContent } = require('./map');
 *   const content = mapContent({ widgets, site });
 */

const fs = require('fs');
const path = require('path');

const REVIEWS_SECTION = /wat zeggen onze klanten|reviews|beoordelingen/i;

// ---------------------------------------------------------------------------
// mapContent() — pure, in-memory. No file I/O.
// ---------------------------------------------------------------------------
function mapContent({ widgets, site = null } = {}) {
  const content = {
    site,
    mappedAt: new Date().toISOString(),
    phone: null,
    phone_url: null,
    hero: { title: null, subtitle: null, text: null, card_title: null, lists: [], images: [], rating: null, rating_text: null },
    top_bar: { items: [], logo: null },
    footer: { description: null },
    sections: [],   // each: { title, text, subsections[], lists[], images[], embeds[] }
    reviews: [],
    faq: [],
    map_address: null,
    embeds: [],     // page-level embeds not tied to a section
    _unassigned: [],
  };

  let cur = null;              // current h2 section
  let pendingReview = null;    // review triple being assembled
  let inReviews = false;

  // Many of these templates lay out a card as [image widget, then its own
  // h2] inside one Elementor column. Since map.js only knows "the current
  // section" from the last h2 seen, an image arriving before its own
  // heading would otherwise attach to the PREVIOUS card. Buffer such images
  // by column path and flush them onto the section once its heading shows up.
  // Only columns that actually contain their OWN h2 get this treatment —
  // a section split across columns (text in column 0, image in column 1,
  // no heading of its own in column 1) still attaches immediately as before.
  const pendingImages = {};    // colPrefix -> [{url, alt, background?}]
  // Paths reset their section/column indices independently per source
  // document (page vs. header vs. footer are each their own walk from 0),
  // so the source must be part of the key or unrelated columns collide.
  const colPrefixOf = (w) => `${w.source}::${(w.path || '').replace(/\s*>\s*widget\[\d+\]$/, '')}`;
  const columnsWithOwnHeading = new Set(
    widgets
      .filter((w) => w.source !== 'header' && w.widgetType === 'heading' && w.content && w.content.level === 'h2')
      .map((w) => colPrefixOf(w))
  );

  const flushReview = () => {
    if (pendingReview && pendingReview.text) content.reviews.push(pendingReview);
    pendingReview = null;
  };

  const pushSection = () => {
    flushReview();
    if (cur && (cur.title || cur.text || cur.lists.length || cur.subsections.length)) {
      delete cur._colPrefix;
      content.sections.push(cur);
    }
    cur = null;
  };

  const target = () => cur || content.hero; // where lists/images attach

  const attachImage = (w, imgObj) => {
    const colPrefix = colPrefixOf(w);
    if (columnsWithOwnHeading.has(colPrefix)) {
      if (cur && cur._colPrefix === colPrefix) { cur.images.push(imgObj); return; }
      (pendingImages[colPrefix] = pendingImages[colPrefix] || []).push(imgObj);
      return;
    }
    target().images.push(imgObj);
  };

  for (const w of widgets) {
    const c = w.content;

    // ---- HEADER source: top bar + logo, nothing else -----------------------
    if (w.source === 'header') {
      if (w.widgetType === 'icon-list') content.top_bar.items.push(...c.items);
      else if (w.widgetType === 'heading' && c.text && c.text.length < 60) content.top_bar.items.push(c.text);
      else if (w.widgetType === 'image' && !content.top_bar.logo) content.top_bar.logo = c.url;
      else if (w.widgetType === 'button' && c.url && c.url.startsWith('tel:')) {
        content.phone = content.phone || c.text;
        content.phone_url = content.phone_url || c.url;
      }
      continue;
    }

    // ---- FOOTER source: h2 sections route normally (Werkgebied / contact);
    //      loose trailing text becomes footer description -------------------
    if (w.source === 'footer' && w.widgetType === 'text-editor' && !cur) {
      content.footer.description = content.footer.description || c.text;
      continue;
    }

    switch (w.widgetType) {
      case 'heading': {
        const lvl = c.level;

        // paragraph-level headings: review names/locations, rating labels
        if (lvl === 'p' || lvl === 'div' || lvl === 'span') {
          if (inReviews && pendingReview) {
            if (!pendingReview.name) pendingReview.name = c.text;
            else if (!pendingReview.area) { pendingReview.area = c.text; flushReview(); }
            else content._unassigned.push(w);
          } else if (/beveelt ons aan|waardering/i.test(c.text || '')) {
            content.hero.rating_text = content.hero.rating_text || c.text;
          } else {
            content._unassigned.push(w);
          }
          break;
        }

        if (lvl === 'h1' && !content.hero.title) { content.hero.title = c.text; break; }
        if (lvl === 'h2' && content.hero.title && !cur && !content.sections.length && !content.hero.subtitle) {
          content.hero.subtitle = c.text; break;
        }

        if (lvl === 'h2') {
          pushSection();
          const colPrefix = colPrefixOf(w);
          cur = { title: c.text, text: '', subsections: [], lists: [], images: [], embeds: [], _colPrefix: colPrefix };
          if (pendingImages[colPrefix]) {
            cur.images.push(...pendingImages[colPrefix]);
            delete pendingImages[colPrefix];
          }
          inReviews = REVIEWS_SECTION.test(c.text || '');
        } else if (lvl === 'h3' || lvl === 'h4') {
          if (cur) {
            cur.subsections.push({ title: c.text, text: '' });
          } else if (!content.hero.card_title) {
            // An h3 seen before any h2 belongs to the hero's own side-card
            // (e.g. "Daarom kiest u voor <bedrijf>:" heading above its USP
            // list) — capture it instead of losing it to _unassigned.
            content.hero.card_title = c.text;
          } else {
            content._unassigned.push(w);
          }
        } else {
          content._unassigned.push(w);
        }
        break;
      }

      case 'text-editor': {
        if (inReviews) {
          // a new review text starts a new triple
          flushReview();
          pendingReview = { text: c.text, name: null, area: null };
          break;
        }
        if (!cur) {
          if (!content.hero.text) content.hero.text = c.text;
          else content.hero.text += '\n\n' + c.text;
          break;
        }
        const subs = cur.subsections;
        if (subs.length && !subs[subs.length - 1].text) subs[subs.length - 1].text = c.text;
        else if (!cur.text) cur.text = c.text;
        else cur.text += '\n\n' + c.text;
        break;
      }

      case 'icon-list':
        target().lists.push({ order: w.order, items: c.items });
        break;

      case 'image':
        // skip decorative platform logos
        if (/google[\s-]?logo|trustpilot|klantenvertellen/i.test(c.alt || c.url || '')) break;
        if (c.url) attachImage(w, { url: c.url, alt: c.alt });
        break;

      case '_background-image':
        if (c.url) attachImage(w, { url: c.url, alt: '', background: true });
        break;

      case 'button':
        if (c.url && c.url.startsWith('tel:')) {
          content.phone = content.phone || c.text;
          content.phone_url = content.phone_url || c.url;
        } else if (c.text || c.url) {
          content._unassigned.push(w);
        }
        break;

      case 'testimonial':
        content.reviews.push({ name: c.name, area: c.meta, text: c.content });
        break;

      case 'accordion':
      case 'toggle':
        content.faq.push(...c.items);
        break;

      case 'star-rating':
        content.hero.rating = content.hero.rating ?? c.rating;
        break;

      case 'google_maps':
        content.map_address = content.map_address || c.address;
        break;

      case 'html':
      case 'shortcode':
      case 'video': {
        const e = { type: w.widgetType, ...c };
        if (cur) cur.embeds.push(e); else content.embeds.push(e);
        break;
      }

      case 'global-unresolved':
        content._unassigned.push(w);
        break;

      default:
        content._unassigned.push(w);
    }
  }
  pushSection();

  // Safety net: any image whose column never produced a matching h2 (should
  // only happen if a card is missing its heading in the source) — surface it
  // instead of silently dropping it.
  for (const [colPrefix, imgs] of Object.entries(pendingImages)) {
    content._unassigned.push({ widgetType: '_orphan-image', path: colPrefix, content: { images: imgs } });
  }

  return content;
}

// ---------------------------------------------------------------------------
// CLI entrypoint
// ---------------------------------------------------------------------------
function main() {
  const args = process.argv.slice(2);
  const positional = args.filter((a) => !a.startsWith('--'));
  const inputPath = positional[0] || 'extracted.json';
  const outputPath = positional[1] || 'site-content.json';
  const siteArg = args.find((a) => a.startsWith('--site='));
  const site = siteArg ? siteArg.split('=')[1] : null;

  // CLI-only — never reached when this module is required as a library
  // (see the require.main guard below); the ignore comment stops Next.js's
  // build-time tracer from over-conservatively bundling the whole repo.
  const { widgets } = JSON.parse(fs.readFileSync(path.resolve(/* turbopackIgnore: true */ inputPath), 'utf8'));

  const content = mapContent({ widgets, site });

  fs.writeFileSync(outputPath, JSON.stringify(content, null, 2), 'utf8');

  console.log(`✔ Mapped → ${outputPath}`);
  console.log(`  hero: title=${JSON.stringify(content.hero.title)} lists=${content.hero.lists.length} rating=${content.hero.rating}`);
  console.log(`  sections: ${content.sections.length}`);
  for (const s of content.sections) {
    console.log(`    - "${s.title}" (subs=${s.subsections.length}, lists=${s.lists.length}, imgs=${s.images.length}, embeds=${s.embeds.length})`);
  }
  console.log(`  reviews: ${content.reviews.length} | faq: ${content.faq.length} | unassigned: ${content._unassigned.length}`);
}

if (require.main === module) main();

module.exports = { mapContent };
