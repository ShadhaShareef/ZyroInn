(function() {
  const hero = document.querySelector('.hero');
  if (!hero) return;

  const canvas = document.createElement('canvas');
  canvas.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0;';
  hero.style.position = 'relative';
  hero.appendChild(canvas);

  const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setSize(canvas.clientWidth, canvas.clientHeight);

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(45, canvas.clientWidth / canvas.clientHeight, 0.1, 100);
  camera.position.z = 20;

  const geometry = new THREE.IcosahedronGeometry(0.6, 0);
  const material = new THREE.MeshPhysicalMaterial({
    color: new THREE.Color('#6C2BD9'),
    metalness: 0.1,
    roughness: 0.3,
    transparent: true,
    opacity: 0.35,
    clearcoat: 0.3,
  });

  const count = 40;
  const meshes = [];
  for (let i = 0; i < count; i++) {
    const m = new THREE.Mesh(geometry, material.clone());
    m.position.set(
      (Math.random() - 0.5) * 30,
      (Math.random() - 0.5) * 20,
      (Math.random() - 0.5) * 15 - 5
    );
    m.material.color.setHSL(0.73 + Math.random() * 0.08, 0.6, 0.45 + Math.random() * 0.25);
    m.material.opacity = 0.15 + Math.random() * 0.25;
    const s = 0.5 + Math.random() * 1.2;
    m.scale.set(s, s, s);
    m.userData = {
      rotSpeed: { x: (Math.random() - 0.5) * 0.01, y: (Math.random() - 0.5) * 0.01 },
      floatSpeed: 0.2 + Math.random() * 0.3,
      floatPhase: Math.random() * Math.PI * 2,
      baseY: m.position.y,
    };
    scene.add(m);
    meshes.push(m);
  }

  let mouseX = 0, mouseY = 0;
  document.addEventListener('mousemove', (e) => {
    mouseX = (e.clientX / window.innerWidth - 0.5) * 2;
    mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
  });

  function resize() {
    const w = canvas.clientWidth;
    const h = canvas.clientHeight;
    if (canvas.width !== w || canvas.height !== h) {
      renderer.setSize(w, h, false);
      camera.aspect = w / h;
      camera.updateProjectionMatrix();
    }
  }

  function animate(time) {
    resize();
    const t = time * 0.001;
    meshes.forEach((m, i) => {
      m.rotation.x += m.userData.rotSpeed.x;
      m.rotation.y += m.userData.rotSpeed.y;
      m.position.y = m.userData.baseY + Math.sin(t * m.userData.floatSpeed + m.userData.floatPhase) * 0.6;
    });
    camera.position.x += (mouseX * 3 - camera.position.x) * 0.02;
    camera.position.y += (-mouseY * 2 - camera.position.y) * 0.02;
    camera.lookAt(0, 0, -2);
    renderer.render(scene, camera);
    requestAnimationFrame(animate);
  }

  const script = document.createElement('script');
  script.src = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';
  script.onload = () => requestAnimationFrame(animate);
  document.head.appendChild(script);
})();