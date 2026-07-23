#!/usr/bin/env node
/**
 * Elementor Content Extractor — v2
 * --------------------------------
 * Changes vs v1:
 *  1. RESOLVES GLOBAL WIDGETS. When a widget has widgetType "global", its
 *     content lives in a separate elementor_library template post. Export
 *     those templates' _elementor_data into a folder as <templateID>.json
 *     and pass it with --templates=./templates — the extractor recurses
 *     into them inline, in the right position on the page.
 *     Unresolvable templateIDs are reported so you know what to export.
 *  2. Captures BACKGROUND IMAGES from section/column/container settings.
 *  3. Emits every widget with its nesting depth + section context intact.
 *
 * Usage:
 *   node extract.js <elementor-data.json> [output.json] [--templates=./templates]
 *
 * Exporting the global templates (run once per site):
 *   WP-CLI:
 *     wp post list --post_type=elementor_library --fields=ID --format=ids \
 *       | tr ' ' '\n' | while read id; do
 *           wp post meta get $id _elementor_data > templates/$id.json; done
 *   Or SQL (phpMyAdmin) — save each meta_value as templates/<ID>.json:
 *     SELECT p.ID, pm.meta_value FROM wp_posts p
 *     JOIN wp_postmeta pm ON pm.post_id = p.ID AND pm.meta_key='_elementor_data'
 *     WHERE p.post_type = 'elementor_library';
 *
 * Also usable as a library (no file I/O):
 *   const { extract } = require('./extract');
 *   const output = extract({ pageData, header, footer, templates });
 *   // templates is a plain object: { [templateID]: <parsed elementor data> }
 */

const fs = require('fs');
const path = require('path');

// ---------------------------------------------------------------------------
function stripHtml(html) {
  if (typeof html !== 'string') return html;
  return html
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<\/p>\s*<p>/gi, '\n\n')
    .replace(/<[^>]+>/g, '')
    .replace(/&euro;/g, '€')
    .replace(/&amp;/g, '&')
    .replace(/&nbsp;/g, ' ')
    .replace(/&#8211;/g, '–')
    .replace(/&#8217;/g, '’')
    .replace(/&#8216;/g, '‘')
    .replace(/&#8220;/g, '“')
    .replace(/&#8221;/g, '”')
    .replace(/&quot;/g, '"')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .trim();
}

const WIDGET_EXTRACTORS = {
  'heading': (s) => ({ level: s.header_size || 'h2', text: stripHtml(s.title) }),
  'text-editor': (s) => ({ html: s.editor, text: stripHtml(s.editor) }),
  'icon-list': (s) => ({ items: (s.icon_list || []).map((i) => stripHtml(i.text)) }),
  'button': (s) => ({ text: stripHtml(s.text), url: s.link?.url || null }),
  'image': (s) => ({ url: s.image?.url || null, alt: s.image?.alt || '' }),
  'testimonial': (s) => ({
    content: stripHtml(s.testimonial_content),
    name: stripHtml(s.testimonial_name),
    meta: stripHtml(s.testimonial_job),
  }),
  'accordion': (s) => ({
    items: (s.tabs || []).map((t) => ({ question: stripHtml(t.tab_title), answer: stripHtml(t.tab_content) })),
  }),
  'toggle': (s) => WIDGET_EXTRACTORS['accordion'](s),
  'google_maps': (s) => ({ address: s.address || null }),
  'html': (s) => ({ html: s.html }),
  'shortcode': (s) => ({ shortcode: s.shortcode }),
  'video': (s) => ({ url: s.youtube_url || s.vimeo_url || s.hosted_url?.url || null }),
  'icon-box': (s) => ({ title: stripHtml(s.title_text), description: stripHtml(s.description_text) }),
  'image-box': (s) => ({ title: stripHtml(s.title_text), description: stripHtml(s.description_text), image: s.image?.url || null }),
  'star-rating': (s) => ({ rating: s.rating ?? null, title: stripHtml(s.title) }),
  'divider': () => null, // decorative, skip
  'spacer': () => null,
};

function fallbackExtract(settings) {
  const TEXT_KEYS = /(title|text|editor|content|description|label|caption|html)$/i;
  const out = {};
  for (const [key, val] of Object.entries(settings || {})) {
    if (typeof val === 'string' && val.trim() && TEXT_KEYS.test(key) && !/typography|color|css/i.test(key)) {
      out[key] = stripHtml(val);
    }
  }
  return Object.keys(out).length ? { _unmapped: true, ...out } : null;
}

// ---------------------------------------------------------------------------
// In-memory template store for global widgets — ctx.templates is a plain
// object keyed by templateID (string or number, either works via JS's
// implicit object-key coercion), value is the already-parsed elementor data
// for that template. The CLI entrypoint builds this from a directory of
// <id>.json files; library callers pass it in directly.
// ---------------------------------------------------------------------------
function resolveTemplate(templates, id) {
  if (!templates) return null;
  const data = templates[id];
  return data === undefined ? null : data;
}

// ---------------------------------------------------------------------------
// Walker
// ---------------------------------------------------------------------------
function walk(elements, ctx, sectionPath = []) {
  if (!Array.isArray(elements)) return;

  elements.forEach((el, idx) => {
    const here = [...sectionPath, `${el.elType}[${idx}]`];
    const s = el.settings || {};

    // Background images on sections / columns / containers
    const bg = s.background_image?.url || s.background_overlay_image?.url;
    if (bg && (el.elType === 'section' || el.elType === 'column' || el.elType === 'container')) {
      ctx.results.push({
        order: ctx.results.length,
        elementorId: el.id,
        widgetType: '_background-image',
        source: ctx.source,
        path: here.join(' > '),
        content: { url: bg },
      });
    }

    if (el.elType === 'widget') {
      const type = el.widgetType || 'unknown';

      // ---- GLOBAL WIDGET: recurse into its template ----------------------
      if (type === 'global') {
        const tplId = el.templateID || el.settings?.templateID || null;
        const tpl = tplId ? resolveTemplate(ctx.templates, tplId) : null;
        if (tpl) {
          ctx.resolvedGlobals.push(tplId);
          walk(tpl, ctx, [...here, `global(${tplId})`]);
        } else {
          if (tplId) ctx.missingTemplates.add(tplId);
          // still keep any inline override settings (e.g. overridden title)
          const fb = fallbackExtract(s);
          ctx.results.push({
            order: ctx.results.length,
            elementorId: el.id,
            widgetType: 'global-unresolved',
            templateID: tplId,
            source: ctx.source,
            path: here.join(' > '),
            content: fb || { _empty: true },
          });
        }
        return; // globals have no own `elements`
      }

      const extractor = WIDGET_EXTRACTORS[type];
      const content = extractor !== undefined
        ? (extractor ? extractor(s) : null)   // divider/spacer → null → skip
        : fallbackExtract(s);

      if (content) {
        ctx.results.push({
          order: ctx.results.length,
          elementorId: el.id,
          widgetType: type,
          source: ctx.source,
          path: here.join(' > '),
          content,
        });
      }
    }

    if (Array.isArray(el.elements) && el.elements.length) {
      walk(el.elements, ctx, here);
    }
  });
}

// ---------------------------------------------------------------------------
// extract() — pure, in-memory. No file I/O. Header first (top bar), then the
// page, then footer — natural page order, matching the CLI's behavior.
// ---------------------------------------------------------------------------
function extract({ pageData, header = null, footer = null, templates = null } = {}) {
  const ctx = {
    results: [],
    templates,
    missingTemplates: new Set(),
    resolvedGlobals: [],
    source: 'page',
  };

  if (header) {
    ctx.source = 'header';
    walk(header, ctx);
  }
  ctx.source = 'page';
  walk(pageData, ctx);
  if (footer) {
    ctx.source = 'footer';
    walk(footer, ctx);
  }

  const byType = {};
  for (const w of ctx.results) byType[w.widgetType] = (byType[w.widgetType] || 0) + 1;
  const unmapped = [...new Set(ctx.results.filter((w) => w.content._unmapped).map((w) => w.widgetType))];

  return {
    extractedAt: new Date().toISOString(),
    totalWidgets: ctx.results.length,
    widgetTypeCounts: byType,
    unmappedWidgetTypes: unmapped,
    resolvedGlobalTemplates: ctx.resolvedGlobals,
    missingGlobalTemplates: [...ctx.missingTemplates],
    widgets: ctx.results,
  };
}

// ---------------------------------------------------------------------------
// CLI entrypoint — reads files from disk, builds the in-memory templates
// map from --templates=<dir>, calls extract(), writes the result.
// ---------------------------------------------------------------------------
function main() {
  const args = process.argv.slice(2);
  const positional = args.filter((a) => !a.startsWith('--'));
  const inputPath = positional[0] || 'sample-elementor-data.json';
  const outputPath = positional[1] || 'extracted.json';
  const tplArg = args.find((a) => a.startsWith('--templates='));
  const templatesDir = tplArg ? path.resolve(tplArg.split('=')[1]) : path.resolve('templates');
  const headerArg = args.find((a) => a.startsWith('--header='));
  const footerArg = args.find((a) => a.startsWith('--footer='));

  // CLI-only file I/O. Never reached when this module is `require()`d as a
  // library (see the `require.main === module` guard below) — the
  // turbopackIgnore comments stop Next.js's build-time file tracer from
  // over-conservatively bundling the whole repo just because it sees a
  // dynamic fs.readFileSync() call somewhere in this file.
  const loadDoc = (p) => {
    let d = JSON.parse(fs.readFileSync(path.resolve(/* turbopackIgnore: true */ p), 'utf8').trim());
    if (typeof d === 'string') d = JSON.parse(d);
    return d;
  };

  const pageData = loadDoc(inputPath);
  const header = headerArg ? loadDoc(headerArg.split('=')[1]) : null;
  const footer = footerArg ? loadDoc(footerArg.split('=')[1]) : null;

  let templates = null;
  if (fs.existsSync(/* turbopackIgnore: true */ templatesDir)) {
    templates = {};
    for (const file of fs.readdirSync(/* turbopackIgnore: true */ templatesDir)) {
      if (!file.endsWith('.json')) continue;
      templates[path.basename(file, '.json')] = loadDoc(path.join(templatesDir, file));
    }
  }

  const output = extract({ pageData, header, footer, templates });

  fs.writeFileSync(outputPath, JSON.stringify({ source: inputPath, ...output }, null, 2), 'utf8');

  console.log(`✔ Extracted ${output.totalWidgets} widgets → ${outputPath}`);
  console.log('  Widget types found:', JSON.stringify(output.widgetTypeCounts));
  if (output.resolvedGlobalTemplates.length) console.log(`  ✔ resolved global templates: ${output.resolvedGlobalTemplates.join(', ')}`);
  if (output.missingGlobalTemplates.length) {
    console.log(`  ⚠ MISSING global templates — export these as templates/<ID>.json: ${output.missingGlobalTemplates.join(', ')}`);
  }
  if (output.unmappedWidgetTypes.length) console.log('  ⚠ Unmapped types:', output.unmappedWidgetTypes.join(', '));
}

if (require.main === module) main();

module.exports = { extract, walk, WIDGET_EXTRACTORS, fallbackExtract };
