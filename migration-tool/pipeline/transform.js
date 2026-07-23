#!/usr/bin/env node
/**
 * Step 3 — Transform to Theme ACF Fields — v2
 * -------------------------------------------
 * Consumes the v2 site-content.json (section-scoped) and produces the
 * import payload with correct placement:
 *   - hero USPs only from hero-zone lists (resolved global widgets)
 *   - map_neighborhoods only from lists inside the "Werkgebied" section
 *   - services_usps from the 3 keyword columns under "Waar kan ..." (first 4)
 *   - each service card gets ITS OWN section image
 *   - reviews from grouped review triples
 *   - rating value/text → hero fields
 *
 * Usage:
 *   node transform.js <site-content.json> [theme-fields.json] [--city=Amsterdam]
 *
 * Also usable as a library (no file I/O):
 *   const { transform } = require('./transform');
 *   const payload = transform({ siteContent, city });
 */

const fs = require('fs');
const path = require('path');

// Strong, distinctive anchors only — sections that don't match any of these
// are resolved by position (see routeSections below), because across 250
// sites the exact wording varies but the template's section ORDER doesn't.
const ANCHOR_ROUTES = [
  { match: /wat zeggen onze klanten|beoordelingen/i,         dest: 'reviews' },
  { match: /waar kan .* (dienst|help)|nog meer.*(dienst|help)/i, dest: 'services_intro' },
  { match: /veelgestelde vragen/i,                           dest: 'faq' },
  { match: /werkgebied/i,                                    dest: 'map' },
  { match: /direct.*hulp|contact/i,                          dest: 'contact' },
  { match: /kosten/i,                                        dest: 'costs' },
  { match: /betrouwbare|waarom kiezen/i,                     dest: 'why_choose' },
];

function anchorRoute(title) {
  for (const r of ANCHOR_ROUTES) if (r.match.test(title || '')) return r.dest;
  return null;
}

// Assigns a destination to every section: known anchors by keyword, then
// the "about / spoed" slot by position (the section right before the
// services intro — whatever it's titled — since every one of these
// templates puts an About-ish block there), then everything left over
// becomes a generic service card.
function routeSections(sections) {
  const dests = sections.map((s) => anchorRoute(s.title));

  const servicesIdx = dests.indexOf('services_intro');
  if (servicesIdx > 0) {
    for (let i = servicesIdx - 1; i >= 0; i--) {
      if (dests[i] === null) { dests[i] = 'about'; break; }
    }
  } else if (dests.length && dests[0] === null) {
    dests[0] = 'about';
  }

  return dests.map((d) => d || 'service_card');
}

function firstNonBgImage(images) {
  const img = (images || []).find((i) => !i.background) || (images || [])[0];
  return img ? img.url : null;
}

// ---------------------------------------------------------------------------
// transform() — pure, in-memory. No file I/O.
// ---------------------------------------------------------------------------
function transform({ siteContent, city: cityOverride } = {}) {
  const src = siteContent;
  const hero = src.hero || {};

  const city = cityOverride || (hero.title || '').split(/\s+/).pop() || '';

  const digits = (src.phone_url || '').replace(/\D/g, '');

  const fields = {
    global_phone_display: src.phone || null,
    global_phone_clean: digits || null,

    // top_bar_items deliberately NOT pulled from the source site — it stays
    // on the theme's ACF default_value / PHP fallback (see top-bar.php),
    // untouched by the pipeline (not even cleared — see MANAGED below).
    // header_logo/footer_logo deliberately NOT set from the source site —
    // these stay on the theme's bundled static images (see THEME_IMAGE_SLUG
    // in functions.php), still overridable per-site via ACF/wp-admin.
    footer_description: (src.footer || {}).description || null,
    // No source equivalent — same "explicit fallback" reasoning as the hero
    // badge/form copy: ACF's default_value stops applying once the field has
    // been saved once, so this pipeline sends it every time.
    header_cta_text: 'Offerte aanvragen',
    header_cta_link: '#offerte-modal',
    // The theme's default_value is a hardcoded "Amstelveen" copyright line —
    // without this every one of the 250 sites would show the wrong city.
    footer_copyright: hero.title
      ? `© ${new Date().getFullYear()} ${hero.title}. Alle rechten voorbehouden.`
      : null,

    hero_title: hero.title ? hero.title.replace(' ', '\n') : null,
    hero_bottom_title: hero.title || null,
    // hero_bottom_subtitle deliberately NOT pulled from the source site —
    // it stays on the theme's ACF default_value / PHP fallback (see
    // hero.php), untouched by the pipeline (not even cleared — see MANAGED below).
    why_choose_heading: hero.subtitle || null,   // 'Uw betrouwbare Elektricien in <city>'
    about_description: hero.text || null,        // Why-Choose description paragraph
    hero_usp_items: (hero.lists[0]?.items || []).map((t) => ({ text: t })),
    hero_card_usp_items: (hero.lists[1]?.items || hero.lists[0]?.items || []).slice(0, 4).map((t) => ({ text: t })),
    // These have no source equivalent on most sites, and ACF's own
    // default_value only shows up before a field has ever been saved once —
    // after that it reads back as a real empty string. So the pipeline sends
    // this generic fallback copy explicitly rather than leaving it to chance.
    hero_badge_text: 'Gratis inspectie!',
    hero_badge_value: 't.w.v. €150,-',
    hero_card_title: hero.card_title ? hero.card_title.replace(/\s+/g, ' ').trim() : 'De Beste Service.',
    hero_card_subtitle: '100% tevredenheidsgarantie.',
    hero_form_heading: 'Ontvang direct de laagste prijs',
    hero_form_button_text: 'Ontvang laagste prijs',
    hero_form_disclaimer: 'Na invullen ontvangt u binnen één dag een reactie en wordt er een offerte voor u opgesteld.',
    // hero_bg_image deliberately NOT set from the source site — stays on
    // the theme's bundled static image, still overridable via ACF/wp-admin.
    hero_rating_value: hero.rating ?? null,
    hero_rating_text: hero.rating_text || null,


    reviews_list: (src.reviews || []).map((r) => ({ text: r.text, name: r.name, location: r.area })),

    faq_items: (src.faq || []).map((f) => ({ question: f.question, answer: f.answer })),
    map_neighborhoods: [],
    services_list: [],
  };

  const sections = src.sections || [];
  const dests = routeSections(sections);

  sections.forEach((s, i) => {
    switch (dests[i]) {

      case 'reviews':
        fields.reviews_heading = s.title;
        break;

      case 'about': {
        fields.about_heading = s.title;           // e.g. 'Spoed Elektricien <city>'
        fields.about_intro_text = s.text || null;
        // about_image deliberately NOT set from the source site — stays on
        // the theme's bundled static image, still overridable via ACF/wp-admin.
        const items = (s.lists || []).flatMap((l) => l.items);
        if (items.length) fields.about_points = items.map((t) => ({ text: t }));
        break;
      }

      case 'why_choose':
        fields.why_choose_heading = fields.why_choose_heading || s.title;
        fields.about_description = fields.about_description || s.text || null;
        fields.why_choose_bullets = (s.subsections || []).map((x) => ({ title: x.title, text: x.text }));
        // about_side_image deliberately NOT set from the source site — stays
        // on the theme's bundled static image, still overridable via ACF/wp-admin.
        break;

      case 'costs': {
        fields.costs_heading = s.title;
        fields.costs_description = s.text || null;
        fields.costs_bullets = (s.subsections || []).map((x) => ({ title: x.title, text: x.text }));
        // costs_side_image deliberately NOT set from the source site — stays
        // on the theme's bundled static image, still overridable via ACF/wp-admin.
        const items = (s.lists || []).flatMap((l) => l.items);
        if (items.length) fields.costs_usps = items.map((t) => ({ text: t }));
        break;
      }

      case 'services_intro': {
        fields.services_heading = s.title;
        fields.services_subheading = s.text || null;
        // services_usps deliberately NOT pulled from the source site — it
        // stays on the theme's ACF default_value / PHP fallback (see
        // services-section.php), untouched by the pipeline (not even
        // cleared — see MANAGED below).
        break;
      }

      case 'faq':
        fields.faq_heading = s.title;
        for (const x of s.subsections || []) {
          if (x.title && x.text) fields.faq_items.push({ question: x.title, answer: x.text });
        }
        break;

      case 'map': {
        fields.map_heading = s.title;
        fields.map_neighborhoods = (s.lists || []).flatMap((l) => l.items).map((n) => ({ name: n }));
        const mapEmbed = (s.embeds || []).find((e) => /maps\.google|google\.com\/maps/i.test(e.html || e.shortcode || ''));
        if (mapEmbed) fields.map_iframe = mapEmbed.html || mapEmbed.shortcode;
        break;
      }

      case 'contact': {
        fields.contact_heading = s.title;
        fields.contact_description = s.text || null;
        const form = (s.embeds || []).find((e) => /teamleader|iframe|\[/i.test(e.html || e.shortcode || ''));
        if (form) fields.contact_iframe_code = form.html || form.shortcode;
        const items = (s.lists || []).flatMap((l) => l.items);
        if (items.length) fields.contact_usps = items.map((t) => ({ text: t }));
        break;
      }

      case 'service_card':
      default:
        fields.services_list.push({
          title: s.title,
          description: s.text || '',
          image: firstNonBgImage(s.images),
          faqs: (s.subsections || []).map((x) => ({ question: x.title, answer: x.text })),
        });
    }
  });

  // Page-level fallbacks for embeds not caught inside a section
  if (!fields.contact_iframe_code) {
    const tl = (src.embeds || []).find((e) => /teamleader/i.test(e.html || ''));
    if (tl) fields.contact_iframe_code = tl.html;
  }
  if (!fields.map_iframe && src.map_address) {
    fields.map_iframe = `<iframe src="https://maps.google.com/maps?q=${encodeURIComponent(src.map_address)}&t=m&z=12&output=embed&iwloc=near" loading="lazy"></iframe>`;
  }

  const cleaned = {};
  for (const [k, v] of Object.entries(fields)) {
    const empty = v === null || v === '' || (Array.isArray(v) && v.length === 0);
    if (!empty) cleaned[k] = v;
  }

  // Fields this pipeline manages; unfilled ones are cleared server-side so
  // stale values from earlier pushes never survive. top_bar_items,
  // hero_bottom_subtitle, and services_usps are deliberately excluded —
  // the pipeline never pulls them from the source site, so they should be
  // skipped entirely (left on their ACF default/PHP fallback), not cleared.
  const MANAGED = [
    'global_phone_display','global_phone_clean',
    'footer_description','header_logo','footer_logo','footer_copyright',
    'header_cta_text','header_cta_link',
    'hero_title','hero_bottom_title','hero_usp_items',
    'hero_card_usp_items','hero_bg_image','hero_rating_value','hero_rating_text',
    'hero_badge_text','hero_badge_value','hero_card_title','hero_card_subtitle',
    'hero_form_heading','hero_form_button_text','hero_form_disclaimer',
    'about_heading','about_intro_text','about_image','about_points',
    'why_choose_heading','about_description','why_choose_bullets','about_side_image',
    'reviews_heading','reviews_list',
    'services_heading','services_subheading','services_list',
    'costs_heading','costs_description','costs_bullets','costs_side_image','costs_usps',
    'faq_heading','faq_items',
    'map_heading','map_neighborhoods','map_iframe',
    'contact_heading','contact_description','contact_iframe_code','contact_usps',
  ];
  const clear = MANAGED.filter((k) => !(k in cleaned));

  return {
    site: src.site || null,
    city,
    generatedAt: new Date().toISOString(),
    sideload_images: true,
    clear,
    fields: cleaned,
  };
}

// ---------------------------------------------------------------------------
// CLI entrypoint
// ---------------------------------------------------------------------------
function main() {
  const args = process.argv.slice(2);
  const positional = args.filter((a) => !a.startsWith('--'));
  const inputPath = positional[0] || 'site-content.json';
  const outputPath = positional[1] || 'theme-fields.json';
  const cityArg = args.find((a) => a.startsWith('--city='));

  // CLI-only — never reached when this module is required as a library
  // (see the require.main guard below); the ignore comment stops Next.js's
  // build-time tracer from over-conservatively bundling the whole repo.
  const siteContent = JSON.parse(fs.readFileSync(path.resolve(/* turbopackIgnore: true */ inputPath), 'utf8'));

  const payload = transform({ siteContent, city: cityArg ? cityArg.split('=')[1] : undefined });

  fs.writeFileSync(outputPath, JSON.stringify(payload, null, 2), 'utf8');
  console.log(`✔ Transformed → ${outputPath}`);
  console.log(`  city: ${payload.city}`);
  console.log(`  fields (${Object.keys(payload.fields).length}): ${Object.keys(payload.fields).join(', ')}`);
  const missing = ['hero_usp_items', 'map_neighborhoods', 'contact_iframe_code', 'map_iframe', 'reviews_list']
    .filter((k) => !payload.fields[k]);
  if (missing.length) console.log(`  ⚠ still missing (likely inside unexported global templates): ${missing.join(', ')}`);
  if (payload.clear.length) console.log(`  ↺ will clear on push: ${payload.clear.join(', ')}`);
}

if (require.main === module) main();

module.exports = { transform };
