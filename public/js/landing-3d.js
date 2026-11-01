/**
 * siCare - 3D Floating Interactive Isometric Grid
 * - 9 individual 3D beveled blocks with Official Google Solid Colors (Blue, Red, Yellow, Green)
 * - Extra-large, centered 2D icons in crisp white with subtle depth shadow
 * - Staggered floating wave animation
 * - Interactive raycaster hover (blocks elevate and react on hover/click)
 * - Mouse parallax & 3D tilt
 * - Transparent background (no outer dark container)
 */

(function () {
    const container = document.getElementById('sicare-3d-container');
    if (!container || typeof THREE === 'undefined') return;

    // --- Official Google Solid Colors & 9 Items with Unicode fallbacks ---
    const gridItems = [
        // Row 0
        { row: 0, col: 0, icon: 'work', unicode: '\ue8f9', color: '#4285F4', label: 'Lowongan Kerja' },       // Google Blue
        { row: 0, col: 1, icon: 'badge', unicode: '\uea67', color: '#EA4335', label: 'Kartu Pegawai' },       // Google Red
        { row: 0, col: 2, icon: 'groups', unicode: '\uf233', color: '#34A853', label: 'Struktur Tim' },       // Google Green
        // Row 1
        { row: 1, col: 0, icon: 'assignment', unicode: '\ue85d', color: '#FBBC04', label: 'Tugas & Cuti' },   // Google Yellow
        { row: 1, col: 1, icon: 'engineering', unicode: '\uea3d', color: '#1a73e8', label: 'Operasional' },   // Google Deep Blue
        { row: 1, col: 2, icon: 'corporate_fare', unicode: '\ue7e7', color: '#d93025', label: 'Departemen' }, // Google Deep Red
        // Row 2
        { row: 2, col: 0, icon: 'co_present', unicode: '\ueaf0', color: '#1e8e3e', label: 'Presensi ESS' },   // Google Deep Green
        { row: 2, col: 1, icon: 'leaderboard', unicode: '\uf20c', color: '#f9ab00', label: 'KPI & Laporan' }, // Google Deep Amber
        { row: 2, col: 2, icon: 'handshake', unicode: '\uebc5', color: '#4285F4', label: 'Onboarding' }       // Google Blue
    ];

    // --- Scene, Camera, Renderer ---
    const scene = new THREE.Scene();

    const width = container.clientWidth || 520;
    const height = container.clientHeight || 520;

    // Isometric-angled perspective camera
    const camera = new THREE.PerspectiveCamera(38, width / height, 0.1, 1000);
    camera.position.set(13.2, 17.0, 18.2);
    camera.lookAt(0, -0.4, 0);

    const renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true,
        powerPreference: 'high-performance'
    });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05; // Balanced exposure to keep colors deep and saturated
    container.innerHTML = '';
    container.appendChild(renderer.domElement);

    // --- Master Pivot Group ---
    const masterGroup = new THREE.Group();
    scene.add(masterGroup);

    // Initial slight isometric angle
    masterGroup.rotation.y = -Math.PI / 8; // -22.5 deg
    masterGroup.rotation.x = Math.PI / 14; // ~12 deg

    // --- Vibrant Directional Lighting ---
    const ambientLight = new THREE.AmbientLight(0xffffff, 1.15); // Not over-bright to maintain solid saturation
    scene.add(ambientLight);

    const hemiLight = new THREE.HemisphereLight(0xffffff, 0x334155, 0.65);
    scene.add(hemiLight);

    const sunLight = new THREE.DirectionalLight(0xffffff, 1.9);
    sunLight.position.set(16, 26, 18);
    sunLight.castShadow = true;
    sunLight.shadow.mapSize.width = 1024;
    sunLight.shadow.mapSize.height = 1024;
    sunLight.shadow.camera.near = 5;
    sunLight.shadow.camera.far = 60;
    const d = 12;
    sunLight.shadow.camera.left = -d;
    sunLight.shadow.camera.right = d;
    sunLight.shadow.camera.top = d;
    sunLight.shadow.camera.bottom = -d;
    sunLight.shadow.bias = -0.0004;
    scene.add(sunLight);

    const fillLight = new THREE.DirectionalLight(0x93c5fd, 0.4);
    fillLight.position.set(-16, 12, -15);
    scene.add(fillLight);

    // --- Soft Contact Ground Shadow ---
    const shadowPlaneGeo = new THREE.PlaneGeometry(32, 32);
    const shadowPlaneMat = new THREE.ShadowMaterial({ opacity: 0.16 });
    const shadowPlane = new THREE.Mesh(shadowPlaneGeo, shadowPlaneMat);
    shadowPlane.rotation.x = -Math.PI / 2;
    shadowPlane.position.y = -2.4;
    shadowPlane.receiveShadow = true;
    masterGroup.add(shadowPlane);

    // --- Geometry: Rounded 3D Block (Enlarged size) ---
    const blockSize = 3.55;        // Large chunky block size
    const blockThickness = 0.95;   // 3D extruded depth
    const cornerRadius = 0.65;     // Smooth rounded corner

    // Create a 2D rounded rectangle shape
    const shape = new THREE.Shape();
    const half = blockSize / 2;
    const r = cornerRadius;
    shape.moveTo(-half + r, -half);
    shape.lineTo(half - r, -half);
    shape.quadraticCurveTo(half, -half, half, -half + r);
    shape.lineTo(half, half - r);
    shape.quadraticCurveTo(half, half, half - r, half);
    shape.lineTo(-half + r, half);
    shape.quadraticCurveTo(-half, half, -half, half - r);
    shape.lineTo(-half, -half + r);
    shape.quadraticCurveTo(-half, -half, -half + r, -half);

    const extrudeSettings = {
        depth: blockThickness,
        bevelEnabled: true,
        bevelSegments: 6,
        steps: 1,
        bevelSize: 0.2,
        bevelThickness: 0.2
    };
    const blockGeo = new THREE.ExtrudeGeometry(shape, extrudeSettings);
    blockGeo.rotateX(Math.PI / 2);
    blockGeo.center();

    // --- Texture Generator: Solid Google Color with Extra-Large Centered 2D Icon ---
    function makeIconTexture(iconName, unicodeGlyph, solidHex) {
        const cvs = document.createElement('canvas');
        cvs.width = 512;
        cvs.height = 512;
        const ctx = cvs.getContext('2d');

        function renderCanvas() {
            ctx.clearRect(0, 0, cvs.width, cvs.height);

            // Rich Solid Google Color
            ctx.fillStyle = solidHex;
            ctx.fillRect(0, 0, 512, 512);

            // Crisp subtle inner border
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.22)';
            ctx.lineWidth = 10;
            ctx.strokeRect(5, 5, 502, 502);

            // Extra Large Full Centered 2D Icon (360px)
            ctx.save();
            ctx.fillStyle = '#ffffff';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            // High-contrast clean drop shadow under white icon
            ctx.shadowColor = 'rgba(0, 0, 0, 0.25)';
            ctx.shadowBlur = 18;
            ctx.shadowOffsetY = 6;

            ctx.font = 'normal 360px "Material Symbols Outlined", sans-serif';

            // Center alignment with optical compensation
            const glyphToDraw = unicodeGlyph || iconName;
            ctx.fillText(glyphToDraw, 256, 262);
            ctx.restore();

            // Subtle top gloss sheen for sleek 3D look
            const sheen = ctx.createLinearGradient(0, 0, 512, 512);
            sheen.addColorStop(0, 'rgba(255, 255, 255, 0.16)');
            sheen.addColorStop(0.35, 'rgba(255, 255, 255, 0.0)');
            sheen.addColorStop(1, 'rgba(0, 0, 0, 0.10)');
            ctx.fillStyle = sheen;
            ctx.fillRect(0, 0, 512, 512);
        }

        renderCanvas();
        const tex = new THREE.CanvasTexture(cvs);
        tex.anisotropy = 4;

        if (document.fonts) {
            document.fonts.ready.then(() => {
                renderCanvas();
                tex.needsUpdate = true;
            });
        }

        return tex;
    }

    // --- Build 3x3 Grid of 3D Blocks ---
    const blockSpacing = 4.45;
    const blocks = [];

    gridItems.forEach((item) => {
        const topTexture = makeIconTexture(item.icon, item.unicode, item.color);

        // Solid rich side material
        const sideMat = new THREE.MeshStandardMaterial({
            color: new THREE.Color(item.color),
            roughness: 0.24,
            metalness: 0.08
        });

        const topMat = new THREE.MeshStandardMaterial({
            map: topTexture,
            roughness: 0.22,
            metalness: 0.05
        });

        // Index 0: top/bottom cap faces, Index 1: beveled sides
        const blockMaterials = [topMat, sideMat];
        const mesh = new THREE.Mesh(blockGeo, blockMaterials);

        // Coordinates (-1, 0, 1 on X and Z)
        const posX = (item.col - 1) * blockSpacing;
        const posZ = (item.row - 1) * blockSpacing;

        mesh.position.set(posX, 0, posZ);
        mesh.castShadow = true;
        mesh.receiveShadow = true;

        mesh.userData = {
            baseX: posX,
            baseZ: posZ,
            baseY: 0,
            targetY: 0,
            currentY: 0,
            row: item.row,
            col: item.col,
            color: item.color,
            isHovered: false
        };

        masterGroup.add(mesh);
        blocks.push(mesh);
    });

    // --- Interactive Raycasting for Hover & Click ---
    const raycaster = new THREE.Raycaster();
    const mouse = new THREE.Vector2(-999, -999);
    let hoveredBlock = null;

    function updateMousePos(e) {
        const rect = container.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return;
        mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
        mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
    }

    container.addEventListener('mousemove', (e) => {
        updateMousePos(e);

        // Subtle mouse parallax tilt of the entire grid
        const cx = (e.clientX - (container.offsetLeft + container.clientWidth / 2)) / window.innerWidth;
        const cy = (e.clientY - (container.offsetTop + container.clientHeight / 2)) / window.innerHeight;
        targetRotY = -Math.PI / 8 + cx * 0.35;
        targetRotX = Math.PI / 14 + cy * 0.22;
    });

    container.addEventListener('mouseleave', () => {
        mouse.x = -999;
        mouse.y = -999;
        targetRotY = -Math.PI / 8;
        targetRotX = Math.PI / 14;
    });

    container.addEventListener('click', (e) => {
        updateMousePos(e);
        raycaster.setFromCamera(mouse, camera);
        const intersects = raycaster.intersectObjects(blocks);
        if (intersects.length > 0) {
            const hit = intersects[0].object;
            // Elastic click press bounce
            hit.userData.currentY = -0.42;
        }
    });

    // --- Touch Support for Mobile ---
    container.addEventListener('touchmove', (e) => {
        if (e.touches.length > 0) {
            updateMousePos(e.touches[0]);
        }
    }, { passive: true });

    // --- Animation Loop ---
    let clock = new THREE.Clock();
    let isVisible = true;
    let targetRotY = -Math.PI / 8;
    let targetRotX = Math.PI / 14;

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            isVisible = entries[0].isIntersecting;
        }, { threshold: 0.05 });
        observer.observe(container);
    }

    function animate() {
        requestAnimationFrame(animate);
        if (!isVisible) return;

        const elapsedTime = clock.getElapsedTime();

        // Check raycaster hover
        raycaster.setFromCamera(mouse, camera);
        const intersects = raycaster.intersectObjects(blocks);
        const newlyHovered = intersects.length > 0 ? intersects[0].object : null;

        if (newlyHovered !== hoveredBlock) {
            if (hoveredBlock) {
                hoveredBlock.userData.isHovered = false;
            }
            hoveredBlock = newlyHovered;
            if (hoveredBlock) {
                hoveredBlock.userData.isHovered = true;
                container.style.cursor = 'pointer';
            } else {
                container.style.cursor = 'default';
            }
        }

        // Animate each of the 9 blocks
        blocks.forEach((b) => {
            const u = b.userData;
            // 1. Organic wave floating: phase offset by row and col
            const waveY = Math.sin(elapsedTime * 2.2 + (u.row + u.col) * 0.75) * 0.24;

            // 2. Hover elevation boost
            const hoverTarget = u.isHovered ? 0.75 : 0;
            u.targetY = waveY + hoverTarget;

            // Smooth interpolation (lerp)
            u.currentY += (u.targetY - u.currentY) * 0.12;
            b.position.y = u.baseY + u.currentY;

            // Subtle scale pulse on hover
            const targetScale = u.isHovered ? 1.05 : 1.0;
            b.scale.lerp(new THREE.Vector3(targetScale, targetScale, targetScale), 0.1);
        });

        // Smooth master grid rotation (mouse parallax)
        masterGroup.rotation.y += (targetRotY - masterGroup.rotation.y) * 0.06;
        masterGroup.rotation.x += (targetRotX - masterGroup.rotation.x) * 0.06;

        renderer.render(scene, camera);
    }

    animate();

    // --- Responsive Resize ---
    window.addEventListener('resize', () => {
        if (!container) return;
        const newWidth = container.clientWidth;
        const newHeight = container.clientHeight;
        if (newWidth === 0 || newHeight === 0) return;

        camera.aspect = newWidth / newHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(newWidth, newHeight);
    });
})();
