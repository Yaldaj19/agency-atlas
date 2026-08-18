/**
 * بازارهای صادراتی — کرهٔ زمین واقعی (three.js) با تکسچر نقشه.
 * - نقشهٔ واقعی زمین روی کرهٔ سه‌بعدی چرخان + پین لوکیشن HTML روی کشورها.
 * - چرخش خودکار (حول محور قطبی) + نوسان ملایم بالا/پایین؛ درگ افقی/عمودی.
 * - کلیک روی پین یا چیپ کشور → کره نرم می‌چرخد و آن کشور را رو به دوربین می‌آورد + زوم دوربینی.
 * - زوم با نزدیک‌شدن دوربین انجام می‌شود (نه اسکیل CSS) تا layout بخش به هم نریزد.
 */
import * as THREE from './vendor/three.module.js';

const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const PIN_SVG =
  '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' +
  '<path d="M12 2C7.9 2 4.6 5.3 4.6 9.4c0 5.2 6.3 11.6 6.9 12.2.3.3.7.3 1 0 .6-.6 6.9-7 6.9-12.2C19.4 5.3 16.1 2 12 2z"/>' +
  '<circle cx="12" cy="9.4" r="2.6" fill="#fff"/></svg>';

const CAM_FAR = 4.85;   // فاصلهٔ دوربین در حالت عادی (کل کره با فاصله از کناره‌ها)
const CAM_NEAR = 3.95;  // فاصلهٔ دوربین در حالت زوم (ملایم — کره برش نمی‌خورد)
const R = 1;            // شعاع کره

// lat/lng → موقعیت روی کره (منطبق بر تکسچر equirectangular استاندارد)
function latLngToVec3(lat, lng, r) {
  const phi = (90 - lat) * Math.PI / 180;
  const theta = (lng + 180) * Math.PI / 180;
  return new THREE.Vector3(
    -r * Math.sin(phi) * Math.cos(theta),
    r * Math.cos(phi),
    r * Math.sin(phi) * Math.sin(theta)
  );
}

function initGlobe(wrap) {
  if (wrap.dataset.bgInit === '1') return;
  wrap.dataset.bgInit = '1';

  const canvas = wrap.querySelector('.bitak-globe__canvas');
  const stage = wrap.querySelector('.bitak-globe__stage');
  const pinsLayer = wrap.querySelector('.bitak-globe__pins');
  const cfgEl = wrap.querySelector('.bitak-globe__config');
  const backBtn = wrap.querySelector('[data-bg-back]');
  const focusBar = wrap.querySelector('.bitak-globe__focusbar');
  const focusName = wrap.querySelector('.bitak-globe__focusname');
  if (!canvas || !cfgEl || !stage) return;

  let cfg;
  try { cfg = JSON.parse(cfgEl.textContent); } catch (e) { return; }

  const markers = Array.isArray(cfg.markers) ? cfg.markers : [];
  const theme = cfg.theme === 'light' ? 'light' : 'dark';
  const textureUrl = cfg.texture || '';

  // ── صحنهٔ three.js ──
  const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(32, 1, 0.1, 100);
  let camZ = CAM_FAR;
  camera.position.set(0, 0, camZ);

  // گروه چرخندهٔ کره
  const group = new THREE.Group();
  scene.add(group);

  // کرهٔ زمین با تکسچر نقشه
  const geo = new THREE.SphereGeometry(R, 64, 64);
  const mat = new THREE.MeshPhongMaterial({
    color: 0xffffff,
    shininess: 12,
    specular: new THREE.Color(theme === 'light' ? 0x223344 : 0x0d2b2e),
  });
  const earth = new THREE.Mesh(geo, mat);
  group.add(earth);

  if (textureUrl) {
    new THREE.TextureLoader().load(textureUrl, (tex) => {
      tex.colorSpace = THREE.SRGBColorSpace;
      tex.anisotropy = renderer.capabilities.getMaxAnisotropy();
      mat.map = tex;
      mat.needsUpdate = true;
    });
  }

  // هالهٔ اتمسفر با CSS (نرم و طبیعی) مدیریت می‌شود — نه شیدر.

  // نورپردازی
  const amb = new THREE.AmbientLight(0xffffff, theme === 'light' ? 1.05 : 0.75);
  scene.add(amb);
  const dir = new THREE.DirectionalLight(0xffffff, theme === 'light' ? 0.75 : 1.1);
  dir.position.set(-1.4, 0.8, 1.6);
  scene.add(dir);

  // ── مارکرهای درخشان روی سطح کره + پین HTML ──
  const pins = [];
  const dotColor = theme === 'light' ? 0x0d9488 : 0xffcc00;
  markers.forEach((m) => {
    const lat = m.location[0], lng = m.location[1];
    const base = latLngToVec3(lat, lng, R);

    // نقطهٔ نورانی روی خود کره
    const dot = new THREE.Mesh(
      new THREE.SphereGeometry(m.origin ? 0.028 : 0.02, 12, 12),
      new THREE.MeshBasicMaterial({ color: dotColor })
    );
    dot.position.copy(base.clone().multiplyScalar(1.004));
    group.add(dot);

    // پین HTML overlay
    let el = null;
    if (pinsLayer) {
      el = document.createElement('span');
      el.className = 'bitak-globe__pin' + (m.origin ? ' is-origin' : '');
      el.innerHTML =
        '<span class="bitak-globe__halo"></span>' +
        '<span class="bitak-globe__pinico">' + PIN_SVG + '</span>' +
        (m.name ? '<span class="bitak-globe__ptip">' + escapeHtml(m.name) + '</span>' : '');
      pinsLayer.appendChild(el);
      el.addEventListener('click', (ev) => { ev.stopPropagation(); focusTo(lat, lng, m.name || ''); });
    }
    pins.push({ el, lat, lng, vec: base.clone(), name: m.name || '', origin: !!m.origin });
  });

  // ── اندازه‌گذاری ──
  let cssW = 0, cssH = 0;
  function resize() {
    cssW = stage.clientWidth;
    cssH = stage.clientHeight;
    if (!cssW || !cssH) return;
    camera.aspect = cssW / cssH;
    camera.updateProjectionMatrix();
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setSize(cssW, cssH, false);
  }
  resize();
  if ('ResizeObserver' in window) {
    new ResizeObserver(() => resize()).observe(stage);
  } else {
    window.addEventListener('resize', resize);
  }

  // ── مدل چرخش (کواترنیون) ──
  const AXIS_Y = new THREE.Vector3(0, 1, 0);
  const AXIS_X = new THREE.Vector3(1, 0, 0);
  const FRONT = new THREE.Vector3(0, 0, 1);
  const qTarget = new THREE.Quaternion(); // جهت پایه (اسپین/درگ)
  let qFocus = null;                       // جهت هدف هنگام فوکوس
  let zoomed = false, down = false, lx = 0, ly = 0, t = 0;
  const autoPhi = reduce ? 0 : 0.0032;

  // شروع با نمایش مبدأ (ایران) رو به دوربین
  const originM = markers.find((m) => m.origin) || markers[0];
  if (originM) {
    const v0 = latLngToVec3(originM.location[0], originM.location[1], R).normalize();
    qTarget.copy(new THREE.Quaternion().setFromUnitVectors(v0, FRONT));
  }

  const _v = new THREE.Vector3();
  function projectPins() {
    if (!cssW || !cssH) return;
    for (let i = 0; i < pins.length; i++) {
      const p = pins[i];
      if (!p.el) continue;
      _v.copy(p.vec).applyQuaternion(group.quaternion);
      const front = _v.z > 0.02;
      _v.project(camera);
      const sx = (_v.x * 0.5 + 0.5) * cssW;
      const sy = (-_v.y * 0.5 + 0.5) * cssH;
      p.el.style.transform = 'translate(-50%,-100%) translate(' + sx.toFixed(1) + 'px,' + sy.toFixed(1) + 'px)';
      p.el.style.opacity = front ? '1' : '0';
      p.el.style.pointerEvents = front ? 'auto' : 'none';
      p.el.style.zIndex = String(100 + Math.round((_v.z + 1) * 50));
    }
  }

  function animate() {
    if (zoomed && qFocus) {
      group.quaternion.slerp(qFocus, 0.12);
    } else if (!down) {
      if (!reduce) {
        qTarget.multiply(new THREE.Quaternion().setFromAxisAngle(AXIS_Y, autoPhi)); // اسپین حول محور قطبی
        t += 0.006;
      }
      const wob = new THREE.Quaternion().setFromAxisAngle(AXIS_X, Math.sin(t) * 0.16);
      group.quaternion.slerp(wob.multiply(qTarget), 0.15);
    } else {
      group.quaternion.copy(qTarget);
    }
    camZ += ((zoomed ? CAM_NEAR : CAM_FAR) - camZ) * 0.09;
    camera.position.z = camZ;

    renderer.render(scene, camera);
    projectPins();
    requestAnimationFrame(animate);
  }

  // ── فوکوس/بازگشت ──
  function focusTo(lat, lng, name) {
    const v0 = latLngToVec3(lat, lng, R).normalize();
    qFocus = new THREE.Quaternion().setFromUnitVectors(v0, FRONT);
    zoomed = true;
    wrap.classList.add('is-zoomed');
    if (focusBar) focusBar.hidden = false;
    if (focusName) focusName.textContent = name || '';
    pins.forEach((p) => p.el && p.el.classList.toggle('is-active', Math.abs(p.lat - lat) < 0.01 && Math.abs(p.lng - lng) < 0.01));
  }
  function resetView() {
    zoomed = false; qFocus = null;
    wrap.classList.remove('is-zoomed');
    if (focusBar) focusBar.hidden = true;
    pins.forEach((p) => p.el && p.el.classList.remove('is-active'));
  }

  // ── درگ ──
  canvas.addEventListener('pointerdown', (e) => {
    down = true; lx = e.clientX; ly = e.clientY;
    zoomed = false; qFocus = null;
    if (focusBar) focusBar.hidden = true;
    try { canvas.setPointerCapture(e.pointerId); } catch (err) {}
    canvas.style.cursor = 'grabbing';
  });
  const up = () => { down = false; canvas.style.cursor = 'grab'; };
  canvas.addEventListener('pointerup', up);
  canvas.addEventListener('pointercancel', up);
  canvas.addEventListener('pointermove', (e) => {
    if (!down) return;
    const dx = (e.clientX - lx) / 220, dy = (e.clientY - ly) / 260;
    lx = e.clientX; ly = e.clientY;
    const qy = new THREE.Quaternion().setFromAxisAngle(AXIS_Y, dx);
    const qx = new THREE.Quaternion().setFromAxisAngle(AXIS_X, dy);
    qTarget.premultiply(qy).premultiply(qx);
  });

  // چیپ‌های لیست کشورها → فوکوس
  wrap.querySelectorAll('.bitak-globe__country').forEach((btn) => {
    btn.addEventListener('click', () => {
      focusTo(parseFloat(btn.dataset.lat), parseFloat(btn.dataset.lng), btn.dataset.name || btn.textContent.trim());
    });
  });
  if (backBtn) backBtn.addEventListener('click', (e) => { e.stopPropagation(); resetView(); });

  requestAnimationFrame(() => setTimeout(() => wrap.classList.add('is-ready'), 60));
  animate();
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function boot() { document.querySelectorAll('.bitak-globe').forEach(initGlobe); }
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
else boot();
