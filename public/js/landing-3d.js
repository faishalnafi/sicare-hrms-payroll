/**
 * siCare - Interactive 3D Headquarters Isometric Scene
 * Modeled accurately after the reference architectural diorama (Gambar 2):
 * - Multi-story modern white tower with curved facade & ribbon glass windows
 * - Tall vibrant orange vertical architectural wing with vertical typography
 * - Rooftop helipad with landing circle and antenna
 * - Lower wing pavilion with horizontal glass louvers
 * - Ground podium with illuminated entrance & underpass road tunnel
 * - Curved asphalt roadway with animated corporate shuttle vehicle
 * - Miniature low-poly trees and street bollards
 * - Full 360-degree interactive mouse/touch orbit & parallax
 */

(function () {
    const container = document.getElementById('sicare-3d-container');
    if (!container || typeof THREE === 'undefined') return;

    // --- Scene, Camera & Renderer ---
    const scene = new THREE.Scene();

    const width = container.clientWidth || 500;
    const height = container.clientHeight || 500;

    // Isometric perspective camera (narrow FOV gives precise architectural isometric look)
    const camera = new THREE.PerspectiveCamera(32, width / height, 0.1, 1000);
    const defaultCamDist = 32;
    camera.position.set(24, 20, 24);
    camera.lookAt(0, 3.5, 0);

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
    renderer.toneMappingExposure = 1.2;
    container.innerHTML = '';
    container.appendChild(renderer.domElement);

    // --- Master Pivot Group (for smooth user drag rotation & floating) ---
    const worldGroup = new THREE.Group();
    scene.add(worldGroup);

    // --- Lighting Setup (Sunlight + Soft Skylight + Warm Interior Accent) ---
    const ambientLight = new THREE.AmbientLight(0xfff6ee, 1.4);
    scene.add(ambientLight);

    const hemiLight = new THREE.HemisphereLight(0xffffff, 0x1e1b4b, 0.7);
    scene.add(hemiLight);

    // Main Warm Sun (Casting soft shadows)
    const sunLight = new THREE.DirectionalLight(0xfff8ee, 2.2);
    sunLight.position.set(28, 42, 22);
    sunLight.castShadow = true;
    sunLight.shadow.mapSize.width = 2048;
    sunLight.shadow.mapSize.height = 2048;
    sunLight.shadow.camera.near = 10;
    sunLight.shadow.camera.far = 90;
    const d = 16;
    sunLight.shadow.camera.left = -d;
    sunLight.shadow.camera.right = d;
    sunLight.shadow.camera.top = d;
    sunLight.shadow.camera.bottom = -d;
    sunLight.shadow.bias = -0.0004;
    scene.add(sunLight);

    // Soft cool fill light for depth
    const fillLight = new THREE.DirectionalLight(0x60a5fa, 0.6);
    fillLight.position.set(-25, 18, -20);
    scene.add(fillLight);

    // --- Materials Palette ---
    const materials = {
        whiteFacade: new THREE.MeshStandardMaterial({
            color: 0xffffff,
            roughness: 0.28,
            metalness: 0.05
        }),
        warmPlaza: new THREE.MeshStandardMaterial({
            color: 0xfbf8f3,
            roughness: 0.6,
            metalness: 0.02
        }),
        podiumCream: new THREE.MeshStandardMaterial({
            color: 0xf5f0e8,
            roughness: 0.45,
            metalness: 0.02
        }),
        glassDark: new THREE.MeshStandardMaterial({
            color: 0x1e293b,
            roughness: 0.12,
            metalness: 0.8
        }),
        glassGlow: new THREE.MeshStandardMaterial({
            color: 0xfef08a,
            emissive: 0xfef08a,
            emissiveIntensity: 0.6,
            roughness: 0.2
        }),
        orangeAccent: new THREE.MeshStandardMaterial({
            color: 0xf95721, // Vibrant architectural orange from Gambar 2
            roughness: 0.32,
            metalness: 0.08
        }),
        orangeDark: new THREE.MeshStandardMaterial({
            color: 0xd94314,
            roughness: 0.4
        }),
        asphalt: new THREE.MeshStandardMaterial({
            color: 0x2e3846,
            roughness: 0.85,
            metalness: 0.05
        }),
        roadWhite: new THREE.MeshBasicMaterial({ color: 0xffffff }),
        pedestalBase: new THREE.MeshStandardMaterial({
            color: 0x131d47,
            roughness: 0.5,
            metalness: 0.2
        }),
        pedestalEdge: new THREE.MeshStandardMaterial({
            color: 0x2a386d,
            roughness: 0.3,
            metalness: 0.4
        }),
        trunkBrown: new THREE.MeshStandardMaterial({ color: 0x6b3f1f, roughness: 0.85 }),
        foliageGreen1: new THREE.MeshStandardMaterial({ color: 0x15803d, roughness: 0.55 }),
        foliageGreen2: new THREE.MeshStandardMaterial({ color: 0x16a34a, roughness: 0.55 }),
        carWhite: new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.2, metalness: 0.3 }),
        carOrange: new THREE.MeshStandardMaterial({ color: 0xf95721, roughness: 0.2 }),
        headlightYellow: new THREE.MeshBasicMaterial({ color: 0xfef08a })
    };

    // --- 1. Isometric Floating Ground Diorama Base ---
    const baseWidth = 22;
    const baseDepth = 22;
    const baseHeight = 1.0;

    // Beveled pedestal slab
    const baseGeo = new THREE.BoxGeometry(baseWidth, baseHeight, baseDepth);
    const baseMesh = new THREE.Mesh(baseGeo, materials.warmPlaza);
    baseMesh.position.y = -baseHeight / 2;
    baseMesh.receiveShadow = true;
    worldGroup.add(baseMesh);

    // Dark under-pedestal skirt to float nicely inside #0c145e
    const skirtGeo = new THREE.BoxGeometry(baseWidth + 0.6, 0.4, baseDepth + 0.6);
    const skirtMesh = new THREE.Mesh(skirtGeo, materials.pedestalEdge);
    skirtMesh.position.y = -baseHeight - 0.2;
    worldGroup.add(skirtMesh);

    // Subtle isometric paving grid lines
    const gridHelper = new THREE.GridHelper(baseWidth, 14, 0xe2dcd2, 0xebe5da);
    gridHelper.position.y = 0.02;
    worldGroup.add(gridHelper);

    // --- 2. Main Tower Building ("siCare HQ" like Gambar 2) ---
    const towerGroup = new THREE.Group();
    towerGroup.position.set(-0.5, 0, -0.5);
    worldGroup.add(towerGroup);

    const towerWidth = 6.4;
    const towerDepth = 6.4;
    const towerHeight = 11.8;
    const podiumHeight = 2.8;

    // Tower main block
    const towerGeo = new THREE.BoxGeometry(towerWidth, towerHeight, towerDepth);
    const towerMesh = new THREE.Mesh(towerGeo, materials.whiteFacade);
    towerMesh.position.set(0, podiumHeight + towerHeight / 2, 0);
    towerMesh.castShadow = true;
    towerMesh.receiveShadow = true;
    towerGroup.add(towerMesh);

    // Horizontal dark ribbon windows across all floors
    const numFloors = 7;
    for (let f = 0; f < numFloors; f++) {
        const floorY = podiumHeight + 0.8 + (f * 1.5);
        // Glass band
        const glassGeo = new THREE.BoxGeometry(towerWidth + 0.1, 0.65, towerDepth + 0.1);
        const glassMesh = new THREE.Mesh(glassGeo, materials.glassDark);
        glassMesh.position.set(0, floorY, 0);
        glassMesh.castShadow = true;
        towerGroup.add(glassMesh);

        // White vertical louvers / mullions on front and side
        for (let m = -2; m <= 2; m++) {
            const mGeo = new THREE.BoxGeometry(0.18, 0.66, 0.12);
            const mullionFront = new THREE.Mesh(mGeo, materials.whiteFacade);
            mullionFront.position.set(m * 1.25, floorY, towerDepth / 2 + 0.08);
            towerGroup.add(mullionFront);

            const mullionSide = new THREE.Mesh(mGeo, materials.whiteFacade);
            mullionSide.rotation.y = Math.PI / 2;
            mullionSide.position.set(towerWidth / 2 + 0.08, floorY, m * 1.25);
            towerGroup.add(mullionSide);
        }
    }

    // --- 3. Dynamic Texture Signage ("siCare" on tower top) ---
    function makeSignCanvas(title, subtitle) {
        const cvs = document.createElement('canvas');
        cvs.width = 512;
        cvs.height = 256;
        const ctx = cvs.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, cvs.width, cvs.height);

        // Title
        ctx.fillStyle = '#0c145e';
        ctx.font = '800 86px "Ubuntu", sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(title, 240, 110);

        // Orange/green dot
        ctx.fillStyle = '#f95721';
        ctx.beginPath();
        ctx.arc(395, 80, 16, 0, Math.PI * 2);
        ctx.fill();

        if (subtitle) {
            ctx.fillStyle = '#64748b';
            ctx.font = '700 30px "Ubuntu", sans-serif';
            ctx.fillText(subtitle, 256, 185);
        }

        const tex = new THREE.CanvasTexture(cvs);
        tex.anisotropy = 4;
        return tex;
    }

    const towerSignTex = makeSignCanvas('siCare', 'ENTERPRISE HRIS');
    const towerSignGeo = new THREE.PlaneGeometry(3.6, 1.8);
    const towerSignMesh = new THREE.Mesh(towerSignGeo, new THREE.MeshStandardMaterial({ map: towerSignTex, roughness: 0.3 }));
    towerSignMesh.position.set(0, podiumHeight + towerHeight - 1.2, towerDepth / 2 + 0.09);
    towerGroup.add(towerSignMesh);

    // --- 4. Rooftop Helipad & Terrace (like Gambar 2) ---
    const roofTopY = podiumHeight + towerHeight;

    // Helipad circular pad
    const helipadPadGeo = new THREE.CylinderGeometry(2.4, 2.4, 0.16, 32);
    const helipadPad = new THREE.Mesh(helipadPadGeo, materials.podiumCream);
    helipadPad.position.set(0, roofTopY + 0.1, 0);
    towerGroup.add(helipadPad);

    // Orange concentric helipad rings
    const heliRingGeo1 = new THREE.RingGeometry(1.6, 1.85, 32);
    const heliRing1 = new THREE.Mesh(heliRingGeo1, materials.orangeAccent);
    heliRing1.rotation.x = -Math.PI / 2;
    heliRing1.position.set(0, roofTopY + 0.19, 0);
    towerGroup.add(heliRing1);

    // Rooftop elevator penthouse
    const roofBoxGeo = new THREE.BoxGeometry(1.8, 1.3, 1.8);
    const roofBox = new THREE.Mesh(roofBoxGeo, materials.whiteFacade);
    roofBox.position.set(-1.8, roofTopY + 0.75, -1.8);
    roofBox.castShadow = true;
    towerGroup.add(roofBox);

    // Rotating rooftop antenna
    const beaconGroup = new THREE.Group();
    beaconGroup.position.set(-1.8, roofTopY + 1.6, -1.8);
    const mastGeo = new THREE.CylinderGeometry(0.05, 0.06, 0.8, 8);
    const mastMesh = new THREE.Mesh(mastGeo, materials.glassDark);
    beaconGroup.add(mastMesh);
    const dishGeo = new THREE.ConeGeometry(0.35, 0.22, 16);
    const dishMesh = new THREE.Mesh(dishGeo, materials.orangeAccent);
    dishMesh.rotation.x = Math.PI / 2;
    dishMesh.position.y = 0.45;
    beaconGroup.add(dishMesh);
    towerGroup.add(beaconGroup);

    // --- 5. Tall Architectural Orange Wing Structure (Exact match to Gambar 2) ---
    // The prominent vertical orange blade standing behind the tower
    const orangeWingWidth = 1.0;
    const orangeWingHeight = 17.5;
    const orangeWingDepth = 7.8;

    const orangeWingGeo = new THREE.BoxGeometry(orangeWingWidth, orangeWingHeight, orangeWingDepth);
    const orangeWingMesh = new THREE.Mesh(orangeWingGeo, materials.orangeAccent);
    orangeWingMesh.position.set(towerWidth / 2 + 1.2, orangeWingHeight / 2, 0.5);
    orangeWingMesh.castShadow = true;
    orangeWingMesh.receiveShadow = true;
    towerGroup.add(orangeWingMesh);

    // Vertical architectural slats on the orange blade
    for (let sl = -3.2; sl <= 3.2; sl += 0.55) {
        const slatGeo = new THREE.BoxGeometry(0.18, orangeWingHeight - 0.4, 0.15);
        const slatMesh = new THREE.Mesh(slatGeo, materials.orangeDark);
        slatMesh.position.set(towerWidth / 2 + 0.65, orangeWingHeight / 2, 0.5 + sl);
        towerGroup.add(slatMesh);
    }

    // Vertical Typography Canvas Texture on Orange Blade
    function makeVerticalTextCanvas() {
        const cvs = document.createElement('canvas');
        cvs.width = 256;
        cvs.height = 512;
        const ctx = cvs.getContext('2d');
        ctx.fillStyle = '#f95721';
        ctx.fillRect(0, 0, cvs.width, cvs.height);

        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 36px "Ubuntu", sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        ctx.fillText('Karier', 128, 120);
        ctx.fillText('Prestasi', 128, 180);
        ctx.fillText('Tumbuh', 128, 240);

        const tex = new THREE.CanvasTexture(cvs);
        return tex;
    }
    const vertTextTex = makeVerticalTextCanvas();
    const vertTextGeo = new THREE.PlaneGeometry(0.8, 3.2);
    const vertTextMesh = new THREE.Mesh(vertTextGeo, new THREE.MeshBasicMaterial({ map: vertTextTex, side: THREE.DoubleSide }));
    vertTextMesh.rotation.y = Math.PI / 2;
    vertTextMesh.position.set(towerWidth / 2 + 1.25 + 0.51, orangeWingHeight - 3.0, 0.5);
    towerGroup.add(vertTextMesh);

    // --- 6. Lower Podium, Entrance Lobby, & Underpass Tunnel (like Gambar 2) ---
    // Ground podium with "Gawat Darurat 24 Jam" / "Lobby Utama" sign
    const podiumGeo = new THREE.BoxGeometry(9.6, podiumHeight, 9.6);
    const podiumMesh = new THREE.Mesh(podiumGeo, materials.podiumCream);
    podiumMesh.position.set(0.6, podiumHeight / 2, 0.5);
    podiumMesh.castShadow = true;
    podiumMesh.receiveShadow = true;
    towerGroup.add(podiumMesh);

    // Podium facade sign banner ("Lobby Utama 24 Jam")
    function makeBannerCanvas(text) {
        const cvs = document.createElement('canvas');
        cvs.width = 512;
        cvs.height = 96;
        const ctx = cvs.getContext('2d');
        ctx.fillStyle = '#f5f0e8';
        ctx.fillRect(0, 0, cvs.width, cvs.height);
        ctx.fillStyle = '#0c145e';
        ctx.font = 'bold 38px "Ubuntu", sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, 256, 48);
        return new THREE.CanvasTexture(cvs);
    }
    const bannerTex = makeBannerCanvas('Pintu Masuk Utama • HRIS');
    const bannerGeo = new THREE.PlaneGeometry(4.8, 0.9);
    const bannerMesh = new THREE.Mesh(bannerGeo, new THREE.MeshStandardMaterial({ map: bannerTex }));
    bannerMesh.position.set(0, podiumHeight - 0.5, 5.35);
    towerGroup.add(bannerMesh);

    // Road Tunnel Arch Cutout with warm interior lighting (like Gambar 2)
    const tunnelWidth = 2.4;
    const tunnelHeight = 2.0;
    const tunnelGeo = new THREE.BoxGeometry(tunnelWidth, tunnelHeight, 3.8);
    const tunnelMat = new THREE.MeshBasicMaterial({ color: 0x0f172a });
    const tunnelMesh = new THREE.Mesh(tunnelGeo, tunnelMat);
    tunnelMesh.position.set(2.6, tunnelHeight / 2, 4.4);
    towerGroup.add(tunnelMesh);

    // Orange glowing plus / symbol above tunnel (like in Gambar 2)
    const plusGeoH = new THREE.BoxGeometry(0.8, 0.25, 0.1);
    const plusGeoV = new THREE.BoxGeometry(0.25, 0.8, 0.1);
    const plusMeshH = new THREE.Mesh(plusGeoH, materials.orangeAccent);
    const plusMeshV = new THREE.Mesh(plusGeoV, materials.orangeAccent);
    const plusGroup = new THREE.Group();
    plusGroup.add(plusMeshH, plusMeshV);
    plusGroup.position.set(2.6, tunnelHeight + 0.6, 5.36);
    towerGroup.add(plusGroup);

    // Warm entrance glass door glow
    const lobbyDoorGeo = new THREE.PlaneGeometry(3.2, 1.8);
    const lobbyDoor = new THREE.Mesh(lobbyDoorGeo, materials.glassGlow);
    lobbyDoor.position.set(-1.8, 0.9, 5.35);
    towerGroup.add(lobbyDoor);

    // Right Low-Profile Pavilion Building (like the right wing in Gambar 2)
    const rightWingGeo = new THREE.BoxGeometry(5.2, 2.0, 7.2);
    const rightWing = new THREE.Mesh(rightWingGeo, materials.whiteFacade);
    rightWing.position.set(7.5, 1.0, -0.8);
    rightWing.castShadow = true;
    rightWing.receiveShadow = true;
    towerGroup.add(rightWing);

    // Horizontal window strip on right wing
    const rightWingGlass = new THREE.Mesh(new THREE.BoxGeometry(5.3, 1.1, 6.6), materials.glassDark);
    rightWingGlass.position.set(7.5, 1.0, -0.8);
    towerGroup.add(rightWingGlass);

    // --- 7. Curved Roadway with White Dashes (like Gambar 2) ---
    // Smooth CatmullRom spline loop matching Gambar 2
    const roadCurve = new THREE.CatmullRomCurve3([
        new THREE.Vector3(-10, 0.05, 9.5),
        new THREE.Vector3(-5, 0.05, 8.5),
        new THREE.Vector3(-0.5, 0.05, 7.5),
        new THREE.Vector3(2.1, 0.05, 5.5),   // Approaching tunnel
        new THREE.Vector3(2.1, 0.05, 2.5),   // Inside underpass tunnel
        new THREE.Vector3(1.8, 0.05, -2.5),
        new THREE.Vector3(-2.0, 0.05, -6.5),
        new THREE.Vector3(-8.0, 0.05, -7.0),
        new THREE.Vector3(-10.5, 0.05, 1.0)
    ], true);

    // Extrude road mesh
    const roadShape = new THREE.Shape();
    roadShape.moveTo(-1.25, 0);
    roadShape.lineTo(1.25, 0);
    roadShape.lineTo(1.25, 0.08);
    roadShape.lineTo(-1.25, 0.08);
    roadShape.closePath();

    const roadGeo = new THREE.ExtrudeGeometry(roadShape, {
        extrudePath: roadCurve,
        steps: 140,
        bevelEnabled: false
    });
    const roadMesh = new THREE.Mesh(roadGeo, materials.asphalt);
    roadMesh.receiveShadow = true;
    worldGroup.add(roadMesh);

    // White dashed center line
    const dashCount = 38;
    const dashGroup = new THREE.Group();
    for (let i = 0; i < dashCount; i++) {
        const u = i / dashCount;
        const pt = roadCurve.getPoint(u);
        const tangent = roadCurve.getTangent(u);

        const dashGeo = new THREE.BoxGeometry(0.12, 0.02, 0.45);
        const dashMesh = new THREE.Mesh(dashGeo, materials.roadWhite);
        dashMesh.position.set(pt.x, 0.16, pt.z);
        dashMesh.quaternion.setFromUnitVectors(new THREE.Vector3(0, 0, 1), tangent);
        dashGroup.add(dashMesh);
    }
    worldGroup.add(dashGroup);

    // --- 8. Animated Miniature Vehicle (Ambulance / Corporate Shuttle van like Gambar 2) ---
    const carGroup = new THREE.Group();

    // Van Body
    const carBodyGeo = new THREE.BoxGeometry(0.95, 0.65, 1.7);
    const carBody = new THREE.Mesh(carBodyGeo, materials.carWhite);
    carBody.position.y = 0.52;
    carBody.castShadow = true;
    carGroup.add(carBody);

    // Orange Stripe along body (like Gambar 2)
    const carStripeGeo = new THREE.BoxGeometry(0.97, 0.14, 1.6);
    const carStripe = new THREE.Mesh(carStripeGeo, materials.carOrange);
    carStripe.position.y = 0.52;
    carGroup.add(carStripe);

    // Dark cabin windows
    const carCabGeo = new THREE.BoxGeometry(0.9, 0.4, 0.95);
    const carCab = new THREE.Mesh(carCabGeo, materials.glassDark);
    carCab.position.set(0, 0.85, 0.1);
    carGroup.add(carCab);

    // Headlights
    const hlGeo = new THREE.BoxGeometry(0.18, 0.12, 0.05);
    const hlL = new THREE.Mesh(hlGeo, materials.headlightYellow);
    hlL.position.set(-0.32, 0.5, 0.86);
    const hlR = hlL.clone();
    hlR.position.x = 0.32;
    carGroup.add(hlL, hlR);

    // 4 Wheels
    const wGeo = new THREE.CylinderGeometry(0.18, 0.18, 0.12, 16);
    wGeo.rotateZ(Math.PI / 2);
    const wMat = materials.glassDark;
    const wFL = new THREE.Mesh(wGeo, wMat);
    wFL.position.set(-0.48, 0.22, 0.5);
    const wFR = wFL.clone();
    wFR.position.x = 0.48;
    const wBL = wFL.clone();
    wBL.position.z = -0.5;
    const wBR = wFR.clone();
    wBR.position.z = -0.5;
    carGroup.add(wFL, wFR, wBL, wBR);

    worldGroup.add(carGroup);

    // --- 9. Miniature Trees & Greenery (like Gambar 2) ---
    function makeLowPolyTree(x, z, scale = 1.0, isDarker = false) {
        const tree = new THREE.Group();
        tree.position.set(x, 0, z);
        tree.scale.set(scale, scale, scale);

        // Trunk
        const trunkGeo = new THREE.CylinderGeometry(0.1, 0.15, 0.9, 8);
        const trunk = new THREE.Mesh(trunkGeo, materials.trunkBrown);
        trunk.position.y = 0.45;
        trunk.castShadow = true;
        tree.add(trunk);

        // Foliage (fluffy geometric dodecahedron clusters)
        const folMat = isDarker ? materials.foliageGreen1 : materials.foliageGreen2;
        const fol1Geo = new THREE.DodecahedronGeometry(0.85, 1);
        const fol1 = new THREE.Mesh(fol1Geo, folMat);
        fol1.position.y = 1.3;
        fol1.castShadow = true;
        tree.add(fol1);

        const fol2Geo = new THREE.DodecahedronGeometry(0.65, 1);
        const fol2 = new THREE.Mesh(fol2Geo, folMat);
        fol2.position.set(0.1, 1.9, 0.05);
        fol2.castShadow = true;
        tree.add(fol2);

        worldGroup.add(tree);
        return tree;
    }

    const trees = [
        makeLowPolyTree(-4.0, 5.8, 1.15, false),
        makeLowPolyTree(-5.8, 4.0, 0.9, true),
        makeLowPolyTree(0.5, 7.8, 0.95, false),
        makeLowPolyTree(4.8, 7.0, 0.85, true),
        makeLowPolyTree(9.2, 3.8, 1.0, false),
        makeLowPolyTree(8.8, 5.6, 0.8, true),
        makeLowPolyTree(-6.5, -4.0, 1.05, false),
        makeLowPolyTree(-7.8, -2.0, 0.85, true),
        // Rooftop garden planters
        makeLowPolyTree(-2.2, 1.4, 0.45, false),
        makeLowPolyTree(1.4, -2.2, 0.45, true)
    ];

    // Miniature street lights
    function makeLampPost(x, z) {
        const lamp = new THREE.Group();
        lamp.position.set(x, 0, z);
        const post = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.05, 1.5, 8), materials.asphalt);
        post.position.y = 0.75;
        lamp.add(post);
        const light = new THREE.Mesh(new THREE.SphereGeometry(0.12, 8, 8), materials.headlightYellow);
        light.position.y = 1.5;
        lamp.add(light);
        worldGroup.add(lamp);
    }
    makeLampPost(-2.2, 6.8);
    makeLampPost(4.4, 6.2);

    // --- Interactive Mouse Orbit & Smooth Inertia Parallax ---
    let mouseX = 0;
    let mouseY = 0;
    let targetRotationY = 0;
    let targetRotationX = 0;
    let isDragging = false;
    let prevMouseX = 0;
    let userRotationY = 0;

    container.addEventListener('mousedown', (e) => {
        isDragging = true;
        prevMouseX = e.clientX;
    });

    window.addEventListener('mouseup', () => {
        isDragging = false;
    });

    window.addEventListener('mousemove', (e) => {
        const rect = container.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return;

        if (isDragging) {
            const deltaX = e.clientX - prevMouseX;
            userRotationY += deltaX * 0.01;
            prevMouseX = e.clientX;
        } else {
            const cx = rect.left + rect.width / 2;
            const cy = rect.top + rect.height / 2;
            mouseX = (e.clientX - cx) / (window.innerWidth / 2);
            mouseY = (e.clientY - cy) / (window.innerHeight / 2);
            targetRotationY = mouseX * 0.22;
            targetRotationX = mouseY * 0.12;
        }
    });

    // Touch support for mobile phones & tablets
    container.addEventListener('touchstart', (e) => {
        if (e.touches.length === 1) {
            isDragging = true;
            prevMouseX = e.touches[0].clientX;
        }
    }, { passive: true });

    window.addEventListener('touchend', () => {
        isDragging = false;
    });

    window.addEventListener('touchmove', (e) => {
        if (isDragging && e.touches.length === 1) {
            const deltaX = e.touches[0].clientX - prevMouseX;
            userRotationY += deltaX * 0.012;
            prevMouseX = e.touches[0].clientX;
        }
    }, { passive: true });

    // --- Animation Loop ---
    let clock = new THREE.Clock();
    let isVisible = true;

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

        // 1. Vehicle moves along curved road loop
        const carSpeed = 0.045;
        const carProgress = (elapsedTime * carSpeed) % 1;
        const carPos = roadCurve.getPointAt(carProgress);
        carGroup.position.copy(carPos);

        const tangent = roadCurve.getTangentAt(carProgress).normalize();
        const lookTarget = carPos.clone().add(tangent);
        carGroup.lookAt(lookTarget);

        // 2. Rooftop radar beacon spinning
        beaconGroup.rotation.y = elapsedTime * 2.8;

        // 3. Subtle floating hover diorama motion
        worldGroup.position.y = Math.sin(elapsedTime * 1.6) * 0.18;

        // 4. Subtle swaying of trees
        trees.forEach((t, i) => {
            t.rotation.z = Math.sin(elapsedTime * 2.2 + i) * 0.035;
        });

        // 5. Smooth user rotation & mouse tilt interpolation (Lerp)
        const totalTargetY = userRotationY + targetRotationY;
        worldGroup.rotation.y += (totalTargetY - worldGroup.rotation.y) * 0.07;
        worldGroup.rotation.x += (targetRotationX - worldGroup.rotation.x) * 0.07;

        renderer.render(scene, camera);
    }

    animate();

    // --- Responsive Resize Handling ---
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
