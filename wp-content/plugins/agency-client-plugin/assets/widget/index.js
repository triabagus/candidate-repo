/**
 * Case Studies headless widget.
 *
 * Consumes /wp-json/acp/v1/case-studies and renders a list. React + htm are loaded as UMD
 * globals (see ACP_Rest::render_widget) so this file ships as-is — no build step.
 *
 * The shortcode [acp_case_studies_widget] puts the #acp-case-studies-widget root on the page
 * and exposes window.ACP_CASE_STUDIES = { restUrl, perPage }.
 */
(function () {
  var ROOT_ID = 'acp-case-studies-widget';
  var config = window.ACP_CASE_STUDIES || {};
  var React = window.React;
  var ReactDOM = window.ReactDOM;
  var htm = window.htm;

  if (!React || !ReactDOM || !htm) {
    console.error('[acp-case-studies] React/ReactDOM/htm not loaded.');
    return;
  }

  var useEffect = React.useEffect;
  var useState = React.useState;
  var html = htm.bind(React.createElement);

  function CaseStudies() {
    var state = useState({ status: 'loading', items: [], error: null });
    var data = state[0];
    var setData = state[1];

    useEffect(function () {
      var url = config.restUrl + '?per_page=' + (config.perPage || 10);
      fetch(url)
        .then(function (res) {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          return res.json();
        })
        .then(function (items) {
          setData({ status: 'ready', items: items, error: null });
        })
        .catch(function (err) {
          setData({ status: 'error', items: [], error: err.message });
        });
    }, []);

    if (data.status === 'loading') {
      return html`<p class="acp-csw-loading">Loading case studies…</p>`;
    }
    if (data.status === 'error') {
      return html`<p class="acp-csw-error">Couldn't load case studies (${data.error}).</p>`;
    }
    if (data.items.length === 0) {
      return html`<p class="acp-csw-empty">No case studies published yet.</p>`;
    }

    return html`
      <ul class="acp-csw-list">
        ${data.items.map(function (cs) {
          return html`
            <li key=${cs.id} class="acp-csw-item">
              ${cs.permalink
                ? html`<a href=${cs.permalink} class="acp-csw-title">${cs.title}</a>`
                : html`<span class="acp-csw-title">${cs.title}</span>`}
              ${cs.headline_metric
                ? html`<span class="acp-csw-metric">${cs.headline_metric}</span>`
                : null}
            </li>
          `;
        })}
      </ul>
    `;
  }

  function mount() {
    var root = document.getElementById(ROOT_ID);
    if (!root) return;
    ReactDOM.createRoot(root).render(html`<${CaseStudies} />`);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
  } else {
    mount();
  }
})();
