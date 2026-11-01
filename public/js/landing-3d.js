/**
 * siCare - 3D Floating Interactive Isometric Grid
 * Replaces the flat 3x3 grid with 9 extruded 3D rounded blocks.
 * Features:
 * - 9 individual 3D beveled blocks with rich corporate colors
 * - 2D crisp icons rendered on the top face
 * - Staggered floating wave animation
 * - Interactive raycaster hover (blocks elevate and react on hover/click)
 * - Mouse parallax & 3D tilt
 * - Transparent background (no outer dark container)
 */

(function () {
    const container = document.getElementById('sicare-3d-container');
    if (!container || typeof THREE === 'undefined') return;

    // --- Grid Items Specification (The 9 cards from Gambar 1) ---
    const gridItems = [
        { row: 0, col: 0, icon: 'work', color: '#243b91', label: 'Lowongan Kerja' },
        { row: 0, col: 1, icon: 'badge', color: '#3e2f6b', label: 'Kartu Pegawai' },
        { row: 0, col: 2, icon: 'groups', color: '#322c7a', label: 'Struktur Tim' },
        { row: 1, col: 0, icon: 'assignment', color: '#1f4277', label: 'Tugas & Cuti' },
        { row: 1, col: 1, icon: 'engineering', color: '#423d61', label: 'Operasional' },
        { row: 1, col: 2, icon: 'corporate_fare', color: '#1e4682', label: 'Departemen' },
        { row: 2, col: 0, icon: 'co_present', color: '#3c2a6d', label: 'Presensi ESS' },
        { row: 2, col: 1, icon: 'leaderboard', color: '#1c3f87', label: 'KPI & Laporan' },
        { row: 2, col: 2, icon: 'handshake', color: '#433660', label: 'Onboarding' }
    ];

    // --- Scene, Camera, Renderer ---
    const scene = new THREE.Scene();

    const width = container.clientWidth || 500;
    const height = container.clientHeight || 500;

    // Camera angled to give clean isometric 3D perspective
    const camera = new THREE.PerspectiveCamera(38, width / height, 0.1, 1000);
    camera.position.set(13, 17, 18);
    camera.lookAt(0, -0.5, 0);

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
    renderer.toneMappingExposure = 1.15;
    container.innerHTML = '';
    container.appendChild(renderer.domElement);

    // --- Master Pivot Group ---
    const masterGroup = new THREE.Group();
    scene.add(masterGroup);

    // Initial slight isometric rotation to give dynamic 3D depth
    masterGroup.rotation.y = -Math.PI / 8; // -22.5 deg
    masterGroup.rotation.x = Math.PI / 14; // ~12 deg

    // --- Lighting Setup ---
    const ambientLight = new THREE.AmbientLight(0xffffff, 1.6);
    scene.add(ambientLight);

    const hemiLight = new THREE.HemisphereLight(0xffffff, 0xdbeafe, 0.8);
    scene.add(hemiLight);

    const sunLight = new THREE.DirectionalLight(0xffffff, 1.8);
    sunLight.position.set(15, 25, 18);
    sunLight.castShadow = true;
    sunLight.shadow.mapSize.width = 1024;
    sunLight.shadow.mapSize.height = 1024;
    sunLight.shadow.camera.near = 5;
    sunLight.shadow.camera.far = 60;
    const d = 10;
    sunLight.shadow.camera.left = -d;
    sunLight.shadow.camera.right = d;
    sunLight.shadow.camera.top = d;
    sunLight.shadow.camera.bottom = -d;
    sunLight.shadow.bias = -0.0005;
    scene.add(sunLight);

    const fillLight = new THREE.DirectionalLight(0x93c5fd, 0.5);
    fillLight.position.set(-15, 12, -15);
    scene.add(fillLight);

    // --- Soft Ground Shadow Receiver ---
    const shadowPlaneGeo = new THREE.PlaneGeometry(28, 28);
    const shadowPlaneMat = new THREE.ShadowMaterial({ opacity: 0.12 });
    const shadowPlane = new THREE.Mesh(shadowPlaneGeo, shadowPlaneMat);
    shadowPlane.rotation.x = -Math.PI / 2;
    shadowPlane.position.y = -2.2;
    shadowPlane.receiveShadow = true;
    masterGroup.add(shadowPlane);

    // --- Geometry: Rounded 3D Block with Bevel ---
    const blockSize = 2.8;
    const blockThickness = 0.9;
    const cornerRadius = 0.45;

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
        bevelSize: 0.16,
        bevelThickness: 0.16
    };
    const blockGeo = new THREE.ExtrudeGeometry(shape, extrudeSettings);
    // Rotate geometry so top face points up (+Y)
    blockGeo.rotateX(Math.PI / 2);
    blockGeo.center();

    // --- Function to Generate 2D Icon Texture for Top Face ---
    function makeIconTexture(iconName, baseHex, callback) {
        const cvs = document.createElement('canvas');
        cvs.width = 512;
        cvs.height = 512;
        const ctx = cvs.getContext('2d');

        function renderCanvas() {
            ctx.clearRect(0, 0, cvs.width, cvs.height);

            // Background with rich smooth gradient
            const grad = ctx.createLinearGradient(0, 0, 0, 512);
            grad.addColorStop(0, baseHex);
            grad.addColorStop(1, adjustColorBrightness(baseHex, -15));
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, 512, 512);

            // Subtle inner glow border
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.15)';
            ctx.lineWidth = 14;
            ctx.strokeRect(7, 7, 498, 498);

            // 2D White Icon in Center (Material Symbols)
            ctx.fillStyle = '#ffffff';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = 'bold 190px "Material Symbols Outlined", sans-serif';
            ctx.fillText(iconName, 256, 256);

            // Subtle top highlight reflection sheen
            const sheen = ctx.createLinearGradient(0, 0, 512, 512);
            sheen.addColorStop(0, 'rgba(255, 255, 255, 0.18)');
            sheen.addColorStop(0.45, 'rgba(255, 255, 255, 0.0)');
            sheen.addColorStop(1, 'rgba(0, 0, 0, 0.12)');
            ctx.fillStyle = sheen;
            ctx.fillRect(0, 0, 512, 512);
        }

        renderCanvas();
        const tex = new THREE.CanvasTexture(cvs);
        tex.anisotropy = 4;

        // Re-render once document fonts are loaded to ensure glyph is crisp
        if (document.fonts) {
            document.fonts.ready.then(() => {
                renderCanvas();
                tex.needsUpdate = true;
            });
        }

        return tex;
    }

    function adjustColorBrightness(hex, percent) {
        let num = parseInt(hex.replace('#', ''), 16);
        let amt = Math.round(2.55 * percent);
        let R = (num >> 16) + amt;
        let G = (num >> 8 & 0x00FF) + amt;
        let B = (num & 0x0000FF) + amt;
        return '#' + (0x1000000 + (R < 255 ? (R < 1 ? 0 : R) : 255) * 0x10000 +
            (G < 255 ? (G < 1 ? 0 : G) : 255) * 0x100 +
            (B < 255 ? (B < 1 ? 0 : B) : 255))
            .toString(16).slice(1);
    }

    // --- Build 3x3 Grid of 3D Blocks ---
    const blockSpacing = 3.65;
    const blocks = [];

    gridItems.forEach((item, index) => {
        const topTexture = makeIconTexture(item.icon, item.color);

        // Materials: Top face with icon texture, sides with smooth matching material
        const sideMat = new THREE.MeshStandardMaterial({
            color: new THREE.Color(item.color),
            roughness: 0.35,
            metalness: 0.12
        });

        const topMat = new THREE.MeshStandardMaterial({
            map: topTexture,
            roughness: 0.28,
            metalness: 0.08
        });

        // In ExtrudeGeometry: index 0 is front/back faces, index 1 is bevel/sides
        const blockMaterials = [topMat, sideMat];
        const mesh = new THREE.Mesh(blockGeo, blockMaterials);

        // Grid Coordinates (-1, 0, 1 on X and Z)
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
            scaleY: 1.0,
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
        targetRotX = Math.PI / 14 + cy * 0.25;
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
            hit.userData.currentY = -0.35;
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
            const waveY = Math.sin(elapsedTime * 2.2 + (u.row + u.col) * 0.75) * 0.22;

            // 2. Hover elevation boost
            const hoverTarget = u.isHovered ? 0.65 : 0;
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
