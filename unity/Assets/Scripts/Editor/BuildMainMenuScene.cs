#if UNITY_EDITOR
using System.IO;
using TMPro;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.Rendering;
using UnityEngine.Rendering.Universal;
using UnityEngine.SceneManagement;

// Menu: The Last Protocol > 1 - Build Environment
// STAGE 2: builds the 3D background of the main menu in a NEW scene called MainMenu
// (dark industrial lab, vault door with amber ring, red beacon, server racks, fog, dust, post-processing).
// It uses the textures you imported in Assets/MainMenu/Imported and the lamp prefabs already in the project.
// It never touches your other scenes. Running it again replaces MainMenu.
public static class BuildMainMenuScene
{
    const string Root = "Assets/MainMenu";
    const string Imported = Root + "/Imported";
    const string Generated = Root + "/Generated";
    const string Materials = Root + "/Materials";
    const string ScenePath = "Assets/Scenes 1/MainMenu.unity";

    // Hall: x -6..6, z -9..9, height 6.2. The vault is on the back wall (+Z), the camera looks at it.
    const float HallW = 12f, HallL = 18f, HallH = 6.2f;
    static readonly Vector3 VaultCenter = new Vector3(0f, 2.6f, 8.2f);

    static readonly Color Amber = new Color(1f, 0.55f, 0.12f);
    static readonly Color RedC = new Color(1f, 0.1f, 0.06f);
    static readonly Color BlueC = new Color(0.25f, 0.55f, 1f);

    static Texture2D concreteDiff, concreteNor, concreteAo, metalDiff, metalNor, metalAo;
    static Texture2D floorMask, metalMask, softDot, rackLeds;
    static Material steel, darkSteel, floorMat, amberGlow, redGlow, blackMat;

    // ------------------------------------------------------------------ entry point

    [MenuItem("The Last Protocol/1 - Build Environment")]
    static void Build()
    {
        if (!EditorSceneManager.SaveCurrentModifiedScenesIfUserWantsTo()) return;
        if (File.Exists(ScenePath) &&
            !EditorUtility.DisplayDialog("Rebuild MainMenu?", "MainMenu.unity already exists. Rebuild the 3D environment (any UI you added later is lost)?", "Rebuild", "Cancel"))
            return;

        AssetDatabase.Refresh();
        EnsureFolder(Root, "Generated"); EnsureFolder(Root, "Materials");

        Scene scene = EditorSceneManager.NewScene(NewSceneSetup.EmptyScene, NewSceneMode.Single);

        LoadTextures();
        MakeCoreMaterials();

        GameObject env = new GameObject("MenuEnvironment");
        Transform e = env.transform;

        BuildShell(e);
        BuildVault(e);
        BuildBeacon(e);
        BuildRacks(e);
        BuildCeiling(e);
        BuildFloorDetails(e);
        BuildSigns(e);
        BuildLights(e);
        BuildDust(e);
        BuildCamera();
        BuildAtmosphere(e);
        BuildPostProcessing();

        Directory.CreateDirectory(Path.GetDirectoryName(ScenePath));
        EditorSceneManager.SaveScene(scene, ScenePath);
        AssetDatabase.SaveAssets();
        Debug.Log("MainMenu environment built and saved: " + ScenePath + ". Open the Game view to see it.");
    }

    // ------------------------------------------------------------------ textures & materials

    static void LoadTextures()
    {
        string c = Imported + "/brushed_concrete_04_2k/textures/brushed_concrete_04_";
        string m = Imported + "/metal_plate_02_2k/textures/metal_plate_02_";

        concreteDiff = Tex(c + "diff_2k.jpg", false, true);
        concreteNor = Tex(c + "nor_gl_2k.png", true, false);
        concreteAo = Tex(c + "ao_2k.jpg", false, false);
        metalDiff = Tex(m + "diff_2k.jpg", false, true);
        metalNor = Tex(m + "nor_gl_2k.png", true, false);
        metalAo = Tex(m + "ao_2k.jpg", false, false);

        // URP wants metallic (R) + smoothness (A) in one map, so we combine the downloaded roughness/metal maps once.
        floorMask = BuildMask("FloorMask", c + "rough_2k.jpg", null, 1.15f, -0.05f);
        metalMask = BuildMask("MetalMask", m + "rough_2k.jpg", m + "metal_2k.jpg", 1.0f, 0.0f);

        softDot = MakeSoftDot();
        rackLeds = MakeRackLeds();

        if (concreteDiff == null || metalDiff == null)
            Debug.LogWarning("Some imported textures were not found; plain colours are used instead. Check Assets/MainMenu/Imported.");
    }

    static Texture2D Tex(string path, bool normal, bool srgb)
    {
        TextureImporter imp = AssetImporter.GetAtPath(path) as TextureImporter;
        if (imp == null) { Debug.LogWarning("Texture missing: " + path); return null; }
        bool dirty = false;
        if (normal && imp.textureType != TextureImporterType.NormalMap) { imp.textureType = TextureImporterType.NormalMap; dirty = true; }
        if (!normal && imp.sRGBTexture != srgb) { imp.sRGBTexture = srgb; dirty = true; }
        if (imp.anisoLevel < 8) { imp.anisoLevel = 8; dirty = true; }
        if (imp.maxTextureSize != 2048) { imp.maxTextureSize = 2048; dirty = true; }
        if (dirty) imp.SaveAndReimport();
        return AssetDatabase.LoadAssetAtPath<Texture2D>(path);
    }

    // R = metallic, A = smoothness (1 - roughness)
    static Texture2D BuildMask(string name, string roughPath, string metalPath, float mul, float add)
    {
        string assetPath = Generated + "/" + name + ".png";
        if (File.Exists(assetPath)) return LoadMask(assetPath);
        if (!File.Exists(roughPath)) return null;

        Texture2D rough = ReadRaw(roughPath);
        Texture2D metal = metalPath != null && File.Exists(metalPath) ? ReadRaw(metalPath) : null;
        if (rough == null) return null;

        int w = rough.width, h = rough.height;
        Color32[] r = rough.GetPixels32();
        Color32[] mt = metal != null && metal.width == w && metal.height == h ? metal.GetPixels32() : null;
        Color32[] o = new Color32[r.Length];
        for (int i = 0; i < r.Length; i++)
        {
            float s = Mathf.Clamp01((1f - r[i].r / 255f) * mul + add);
            byte mv = mt != null ? mt[i].r : (byte)0;
            o[i] = new Color32(mv, 0, 0, (byte)Mathf.RoundToInt(s * 255f));
        }
        Texture2D t = new Texture2D(w, h, TextureFormat.RGBA32, false);
        t.SetPixels32(o);
        File.WriteAllBytes(assetPath, t.EncodeToPNG());
        Object.DestroyImmediate(t); Object.DestroyImmediate(rough); if (metal != null) Object.DestroyImmediate(metal);
        AssetDatabase.ImportAsset(assetPath, ImportAssetOptions.ForceUpdate);
        return LoadMask(assetPath);
    }

    static Texture2D LoadMask(string assetPath)
    {
        TextureImporter imp = AssetImporter.GetAtPath(assetPath) as TextureImporter;
        if (imp != null && (imp.sRGBTexture || imp.alphaSource != TextureImporterAlphaSource.FromInput))
        {
            imp.sRGBTexture = false;
            imp.alphaSource = TextureImporterAlphaSource.FromInput;
            imp.alphaIsTransparency = false;
            imp.SaveAndReimport();
        }
        return AssetDatabase.LoadAssetAtPath<Texture2D>(assetPath);
    }

    static Texture2D ReadRaw(string path)
    {
        Texture2D t = new Texture2D(2, 2, TextureFormat.RGBA32, false);
        return t.LoadImage(File.ReadAllBytes(path)) ? t : null;
    }

    static Texture2D MakeSoftDot()
    {
        string p = Generated + "/SoftDot.png";
        if (!File.Exists(p))
        {
            int n = 128;
            Texture2D t = new Texture2D(n, n, TextureFormat.RGBA32, false);
            for (int y = 0; y < n; y++)
                for (int x = 0; x < n; x++)
                {
                    float d = Vector2.Distance(new Vector2(x, y), new Vector2(n / 2f, n / 2f)) / (n / 2f);
                    float a = Mathf.Clamp01(1f - d); a = a * a * (3f - 2f * a);
                    t.SetPixel(x, y, new Color(1f, 1f, 1f, a));
                }
            File.WriteAllBytes(p, t.EncodeToPNG());
            Object.DestroyImmediate(t);
            AssetDatabase.ImportAsset(p, ImportAssetOptions.ForceUpdate);
        }
        TextureImporter imp = AssetImporter.GetAtPath(p) as TextureImporter;
        if (imp != null && !imp.alphaIsTransparency) { imp.alphaIsTransparency = true; imp.mipmapEnabled = false; imp.SaveAndReimport(); }
        return AssetDatabase.LoadAssetAtPath<Texture2D>(p);
    }

    // small emissive texture of blinking server LEDs
    static Texture2D MakeRackLeds()
    {
        string p = Generated + "/RackLeds.png";
        if (!File.Exists(p))
        {
            int w = 64, h = 160;
            Texture2D t = new Texture2D(w, h, TextureFormat.RGBA32, false);
            Color[] px = new Color[w * h];
            for (int i = 0; i < px.Length; i++) px[i] = Color.black;
            System.Random rnd = new System.Random(11);
            for (int row = 6; row < h - 6; row += 8)
            {
                for (int col = 6; col < w - 6; col += 6)
                {
                    if (rnd.NextDouble() < 0.28) continue;
                    double k = rnd.NextDouble();
                    Color c = k < 0.7 ? new Color(0.15f, 0.55f, 1f) : (k < 0.9 ? new Color(0.1f, 0.9f, 0.9f) : new Color(1f, 0.5f, 0.1f));
                    for (int dy = 0; dy < 2; dy++)
                        for (int dx = 0; dx < 3; dx++)
                            px[(row + dy) * w + col + dx] = c;
                }
            }
            t.SetPixels(px);
            File.WriteAllBytes(p, t.EncodeToPNG());
            Object.DestroyImmediate(t);
            AssetDatabase.ImportAsset(p, ImportAssetOptions.ForceUpdate);
        }
        TextureImporter imp = AssetImporter.GetAtPath(p) as TextureImporter;
        if (imp != null && imp.filterMode != FilterMode.Point) { imp.filterMode = FilterMode.Point; imp.mipmapEnabled = false; imp.SaveAndReimport(); }
        return AssetDatabase.LoadAssetAtPath<Texture2D>(p);
    }

    static void MakeCoreMaterials()
    {
        steel = PbrMat("M_Steel", new Color(0.8f, 0.82f, 0.85f), metalDiff, metalNor, metalMask, metalAo, new Vector2(1f, 1f));
        darkSteel = PbrMat("M_SteelDark", new Color(0.32f, 0.34f, 0.37f), metalDiff, metalNor, metalMask, metalAo, new Vector2(1f, 1f));
        floorMat = PbrMat("M_FloorConcrete", new Color(0.45f, 0.43f, 0.42f), concreteDiff, concreteNor, floorMask, concreteAo, new Vector2(HallW / 3f, HallL / 3f));
        blackMat = PbrMat("M_Black", new Color(0.02f, 0.02f, 0.025f), null, null, null, null, Vector2.one, 0.3f, 0.6f);
        amberGlow = Glow("M_AmberGlow", Amber, 2.2f);
        redGlow = Glow("M_RedGlow", RedC, 5f);
    }

    static Shader Lit() { return Shader.Find("Universal Render Pipeline/Lit"); }

    static Material GetMat(string name, Shader shader)
    {
        string path = Materials + "/" + name + ".mat";
        Material m = AssetDatabase.LoadAssetAtPath<Material>(path);
        if (m == null) { m = new Material(shader); AssetDatabase.CreateAsset(m, path); }
        else m.shader = shader;
        return m;
    }

    static Material PbrMat(string name, Color tint, Texture2D diff, Texture2D nor, Texture2D mask, Texture2D ao, Vector2 tiling, float metallic = 0f, float smooth = 0.5f)
    {
        Material m = GetMat(name, Lit());
        m.SetColor("_BaseColor", tint);
        m.SetTexture("_BaseMap", diff);
        m.SetTextureScale("_BaseMap", tiling);
        if (nor != null) { m.SetTexture("_BumpMap", nor); m.SetFloat("_BumpScale", 1f); m.EnableKeyword("_NORMALMAP"); }
        if (mask != null)
        {
            m.SetTexture("_MetallicGlossMap", mask);
            m.SetFloat("_Smoothness", 1f);
            m.EnableKeyword("_METALLICSPECGLOSSMAP");
        }
        else { m.SetFloat("_Metallic", metallic); m.SetFloat("_Smoothness", smooth); }
        if (ao != null) { m.SetTexture("_OcclusionMap", ao); m.SetFloat("_OcclusionStrength", 1f); m.EnableKeyword("_OCCLUSIONMAP"); }
        EditorUtility.SetDirty(m);
        return m;
    }

    static Material Glow(string name, Color c, float intensity)
    {
        Material m = GetMat(name, Lit());
        m.SetColor("_BaseColor", c * 0.15f);
        m.SetFloat("_Metallic", 0f); m.SetFloat("_Smoothness", 0.3f);
        m.EnableKeyword("_EMISSION");
        m.SetColor("_EmissionColor", c * intensity);
        m.globalIlluminationFlags = MaterialGlobalIlluminationFlags.None;
        EditorUtility.SetDirty(m);
        return m;
    }

    // glowing, see-through material (halos, light spill, dust)
    static Material Additive(string name, Color c, Texture tex, bool particle = false)
    {
        Shader s = Shader.Find(particle ? "Universal Render Pipeline/Particles/Unlit" : "Universal Render Pipeline/Unlit");
        Material m = GetMat(name, s);
        m.SetFloat("_Surface", 1f); m.SetFloat("_Blend", 2f);
        m.SetFloat("_SrcBlend", (float)BlendMode.SrcAlpha); m.SetFloat("_DstBlend", (float)BlendMode.One);
        m.SetFloat("_SrcBlendAlpha", (float)BlendMode.One); m.SetFloat("_DstBlendAlpha", (float)BlendMode.One);
        m.SetFloat("_ZWrite", 0f);
        m.EnableKeyword("_SURFACE_TYPE_TRANSPARENT");
        m.SetOverrideTag("RenderType", "Transparent");
        m.renderQueue = 3000;
        m.SetShaderPassEnabled("ShadowCaster", false);
        m.SetColor("_BaseColor", c);
        m.SetTexture("_BaseMap", tex);
        EditorUtility.SetDirty(m);
        return m;
    }

    static Material Tiled(Material source, string name, Vector2 tiling)
    {
        Material m = GetMat(name, source.shader);
        m.CopyPropertiesFromMaterial(source);
        m.SetTextureScale("_BaseMap", tiling);
        foreach (string k in source.shaderKeywords) m.EnableKeyword(k);
        EditorUtility.SetDirty(m);
        return m;
    }

    // ------------------------------------------------------------------ shell (floor, walls, ceiling)

    static void BuildShell(Transform parent)
    {
        Transform s = Group(parent, "Shell");
        float t = 0.4f;

        Prim(PrimitiveType.Cube, "Floor", s, new Vector3(0, -t / 2f, 0), new Vector3(HallW, t, HallL), floorMat);
        Prim(PrimitiveType.Cube, "Ceiling", s, new Vector3(0, HallH + t / 2f, 0), new Vector3(HallW, t, HallL), darkSteel);

        Material wallBack = Tiled(steel, "M_WallBack", new Vector2(HallW / 2f, HallH / 2f));
        Material wallSide = Tiled(steel, "M_WallSide", new Vector2(HallL / 2f, HallH / 2f));
        Prim(PrimitiveType.Cube, "Wall_Back", s, new Vector3(0, HallH / 2f, HallL / 2f + t / 2f), new Vector3(HallW + 2 * t, HallH, t), wallBack);
        Prim(PrimitiveType.Cube, "Wall_Front", s, new Vector3(0, HallH / 2f, -HallL / 2f - t / 2f), new Vector3(HallW + 2 * t, HallH, t), wallBack);
        Prim(PrimitiveType.Cube, "Wall_Left", s, new Vector3(-HallW / 2f - t / 2f, HallH / 2f, 0), new Vector3(t, HallH, HallL), wallSide);
        Prim(PrimitiveType.Cube, "Wall_Right", s, new Vector3(HallW / 2f + t / 2f, HallH / 2f, 0), new Vector3(t, HallH, HallL), wallSide);

        // vertical pilasters every 3 m on both side walls + a dark skirting
        for (float z = -7.5f; z <= 7.6f; z += 3f)
        {
            Prim(PrimitiveType.Cube, "Pilaster_L", s, new Vector3(-HallW / 2f + 0.2f, HallH / 2f, z), new Vector3(0.4f, HallH, 0.5f), darkSteel);
            Prim(PrimitiveType.Cube, "Pilaster_R", s, new Vector3(HallW / 2f - 0.2f, HallH / 2f, z), new Vector3(0.4f, HallH, 0.5f), darkSteel);
        }
        Prim(PrimitiveType.Cube, "Skirt_L", s, new Vector3(-HallW / 2f + 0.06f, 0.2f, 0), new Vector3(0.12f, 0.4f, HallL), blackMat);
        Prim(PrimitiveType.Cube, "Skirt_R", s, new Vector3(HallW / 2f - 0.06f, 0.2f, 0), new Vector3(0.12f, 0.4f, HallL), blackMat);

        // amber strip lights along the base of both side walls (they reflect in the floor)
        Prim(PrimitiveType.Cube, "StripAmber_L", s, new Vector3(-HallW / 2f + 0.14f, 0.45f, 0), new Vector3(0.03f, 0.04f, HallL - 0.6f), amberGlow);
        Prim(PrimitiveType.Cube, "StripAmber_R", s, new Vector3(HallW / 2f - 0.14f, 0.45f, 0), new Vector3(0.03f, 0.04f, HallL - 0.6f), amberGlow);

        // pipes along the walls
        Material pipe = darkSteel;
        for (int i = 0; i < 3; i++)
        {
            float y = 4.4f + i * 0.38f;
            Cyl("Pipe_L" + i, s, new Vector3(-HallW / 2f + 0.55f, y, 0), new Vector3(0.18f, HallL / 2f - 0.3f, 0.18f), pipe, new Vector3(90, 0, 0));
            Cyl("Pipe_R" + i, s, new Vector3(HallW / 2f - 0.55f, y, 0), new Vector3(0.18f, HallL / 2f - 0.3f, 0.18f), pipe, new Vector3(90, 0, 0));
        }
        for (float z = -7.5f; z <= 7.6f; z += 1.5f)
        {
            Prim(PrimitiveType.Cube, "PipeClamp_L", s, new Vector3(-HallW / 2f + 0.52f, 4.8f, z), new Vector3(0.28f, 1.3f, 0.12f), blackMat);
            Prim(PrimitiveType.Cube, "PipeClamp_R", s, new Vector3(HallW / 2f - 0.52f, 4.8f, z), new Vector3(0.28f, 1.3f, 0.12f), blackMat);
        }
    }

    // ------------------------------------------------------------------ the vault door

    static void BuildVault(Transform parent)
    {
        Transform v = Group(parent, "VaultDoor");
        v.position = VaultCenter;     // everything below is local to the vault centre

        Mesh ringThick = Torus("Torus_OuterRing", 2.75f, 0.2f, 72, 14);
        Mesh ringThin = Torus("Torus_AmberRing", 2.45f, 0.055f, 96, 10);
        Mesh doorRim = Torus("Torus_DoorRim", 1.95f, 0.11f, 72, 12);
        Mesh wheelRim = Torus("Torus_WheelRim", 0.72f, 0.06f, 48, 10);

        // big mounting plate on the wall + bolts at the corners
        Prim(PrimitiveType.Cube, "MountPlate", v, new Vector3(0, 0, 0.55f), new Vector3(7f, 6.2f, 0.45f), darkSteel);
        for (int sx = -1; sx <= 1; sx += 2)
            for (int sy = -1; sy <= 1; sy += 2)
                Cyl("PlateBolt", v, new Vector3(sx * 3.1f, sy * 2.7f, 0.3f), new Vector3(0.32f, 0.08f, 0.32f), steel, new Vector3(90, 0, 0));

        // soft amber halo on the wall behind the ring
        GameObject halo = Prim(PrimitiveType.Quad, "AmberHalo", v, new Vector3(0, 0, 0.3f), new Vector3(8.5f, 8.5f, 1f), Additive("M_AmberHalo", new Color(1f, 0.5f, 0.1f, 0.5f), softDot));
        halo.transform.rotation = Quaternion.identity;
        Object.DestroyImmediate(halo.GetComponent<Collider>());

        // frame: dark opening (what you see when the door opens) + steel ring + glowing amber ring
        Cyl("HoleDark", v, new Vector3(0, 0, 0.35f), new Vector3(4.0f, 0.02f, 4.0f), blackMat, new Vector3(90, 0, 0));
        GameObject behind = Cyl("BehindGlow", v, new Vector3(0, 0, 0.3f), new Vector3(3.9f, 0.01f, 3.9f), Glow("M_PortalGlow", new Color(1f, 0.75f, 0.45f), 3f), new Vector3(90, 0, 0));
        behind.SetActive(false);
        GameObject beOuter = MeshObj("OuterRing", v, ringThick, Vector3.zero, steel);
        GameObject amberRing = MeshObj("AmberRing", v, ringThin, new Vector3(0, 0, -0.18f), amberGlow);
        MeshObj("FrameInner", v, doorRim, new Vector3(0, 0, 0.05f), darkSteel);

        // bolts around the outer ring
        for (int i = 0; i < 24; i++)
        {
            float a = i * Mathf.PI * 2f / 24f;
            Cyl("RingBolt" + i, v, new Vector3(Mathf.Cos(a) * 2.75f, Mathf.Sin(a) * 2.75f, -0.18f), new Vector3(0.16f, 0.06f, 0.16f), steel, new Vector3(90, 0, 0));
        }

        // ---------- the door (hinged on its left edge: pivot at x = -2.0) ----------
        GameObject pivot = new GameObject("DoorAssembly");
        pivot.transform.SetParent(v, false);
        pivot.transform.localPosition = new Vector3(-2.0f, 0f, -0.1f);
        Transform d = pivot.transform;

        Cyl("DoorDisk", d, new Vector3(2.0f, 0, 0), new Vector3(3.95f, 0.17f, 3.95f), Tiled(steel, "M_DoorSteel", new Vector2(1.4f, 1.4f)), new Vector3(90, 0, 0));
        MeshObj("DoorRim", d, doorRim, new Vector3(2.0f, 0, -0.14f), darkSteel);
        Cyl("DoorInset", d, new Vector3(2.0f, 0, -0.17f), new Vector3(2.7f, 0.03f, 2.7f), darkSteel, new Vector3(90, 0, 0));
        for (int i = 0; i < 16; i++)
        {
            float a = i * Mathf.PI * 2f / 16f;
            Cyl("DoorBolt" + i, d, new Vector3(2.0f + Mathf.Cos(a) * 1.62f, Mathf.Sin(a) * 1.62f, -0.2f), new Vector3(0.14f, 0.05f, 0.14f), steel, new Vector3(90, 0, 0));
        }

        // locking lugs sticking out of the door edge into the frame (they retract when opening)
        GameObject lugs = new GameObject("LockBolts");
        lugs.transform.SetParent(d, false);
        lugs.transform.localPosition = new Vector3(2.0f, 0, 0.05f);
        for (int i = 0; i < 8; i++)
        {
            float a = i * Mathf.PI * 2f / 8f + Mathf.PI / 8f;
            GameObject lug = Prim(PrimitiveType.Cube, "Lug" + i, lugs.transform, new Vector3(Mathf.Cos(a) * 2.0f, Mathf.Sin(a) * 2.0f, 0), new Vector3(0.5f, 0.26f, 0.2f), steel);
            lug.transform.localRotation = Quaternion.Euler(0, 0, a * Mathf.Rad2Deg);
        }

        // central wheel
        GameObject wheel = new GameObject("Wheel");
        wheel.transform.SetParent(d, false);
        wheel.transform.localPosition = new Vector3(2.0f, 0, -0.28f);
        Cyl("Hub", wheel.transform, Vector3.zero, new Vector3(0.55f, 0.12f, 0.55f), steel, new Vector3(90, 0, 0));
        MeshObj("WheelRim", wheel.transform, wheelRim, new Vector3(0, 0, -0.04f), steel);
        for (int i = 0; i < 6; i++)
        {
            GameObject sp = Prim(PrimitiveType.Cube, "Spoke" + i, wheel.transform, new Vector3(0, 0, -0.02f), new Vector3(1.44f, 0.09f, 0.08f), steel);
            sp.transform.localRotation = Quaternion.Euler(0, 0, i * 30f);
        }

        // status plate under the wheel
        Prim(PrimitiveType.Cube, "StatusPlate", d, new Vector3(2.0f, -1.2f, -0.2f), new Vector3(0.9f, 0.32f, 0.05f), blackMat);
        GameObject lamp = Prim(PrimitiveType.Sphere, "StatusLamp", d, new Vector3(1.75f, -1.2f, -0.25f), new Vector3(0.14f, 0.14f, 0.14f), redGlow);
        Object.DestroyImmediate(lamp.GetComponent<Collider>());
        TextMeshPro plate = Text("StatusText", d, "SEALED", 1.1f, new Vector3(2.12f, -1.2f, -0.235f), Vector3.zero, new Color(1f, 0.25f, 0.15f));

        // ---------- lights that belong to the vault ----------
        Light front = NewLight("VaultAmberFront", v, LightType.Point, Amber, 6f, 12f, new Vector3(0, 0.4f, -2.8f));
        Light wash = NewLight("VaultAmberWash", v, LightType.Spot, Amber, 9f, 14f, new Vector3(0, 2.6f, -3.2f));
        wash.transform.localRotation = Quaternion.Euler(70f, 0, 0);
        wash.spotAngle = 80f;
        Light behindLight = NewLight("BehindLight", v, LightType.Point, new Color(1f, 0.8f, 0.5f), 8f, 12f, new Vector3(0, 0, -0.9f));
        behindLight.gameObject.SetActive(false);

        // pulse script
        VaultGlow glow = v.gameObject.AddComponent<VaultGlow>();
        SerializedObject so = new SerializedObject(glow);
        SetArray(so, "glowRenderers", new Object[] { amberRing.GetComponent<Renderer>() });
        SetArray(so, "glowLights", new Object[] { front, wash });
        so.ApplyModifiedProperties();

        // quick reference for the next stage (door animation)
        Debug.Log("Vault built. Door parts: DoorAssembly (hinge), Wheel, LockBolts, BehindGlow.");
    }

    static void SetArray(SerializedObject so, string field, Object[] items)
    {
        SerializedProperty p = so.FindProperty(field);
        if (p == null) { Debug.LogWarning("Missing field: " + field); return; }
        p.arraySize = items.Length;
        for (int i = 0; i < items.Length; i++) p.GetArrayElementAtIndex(i).objectReferenceValue = items[i];
    }

    // ------------------------------------------------------------------ beacon

    static void BuildBeacon(Transform parent)
    {
        Transform b = Group(parent, "WarningBeacon");
        b.position = new Vector3(0f, 5.75f, HallL / 2f - 0.55f);

        Prim(PrimitiveType.Cube, "Bracket", b, new Vector3(0, -0.05f, 0.35f), new Vector3(0.7f, 0.5f, 0.15f), darkSteel);
        Cyl("Base", b, Vector3.zero, new Vector3(0.62f, 0.12f, 0.62f), steel, Vector3.zero);
        GameObject lens = Prim(PrimitiveType.Sphere, "Lens", b, new Vector3(0, 0.28f, 0), new Vector3(0.52f, 0.46f, 0.52f), redGlow);
        Object.DestroyImmediate(lens.GetComponent<Collider>());

        GameObject rotor = new GameObject("Rotor");
        rotor.transform.SetParent(b, false);
        rotor.transform.localPosition = new Vector3(0, 0.28f, 0);
        Light beam = NewLight("Beam", rotor.transform, LightType.Spot, RedC, 14f, 16f, Vector3.zero);
        beam.spotAngle = 38f;
        beam.transform.localRotation = Quaternion.Euler(12f, 0, 0);    // slightly downward
        Light glow = NewLight("LensGlow", b, LightType.Point, RedC, 3f, 5f, new Vector3(0, 0.28f, -0.4f));

        MenuBeacon mb = b.gameObject.AddComponent<MenuBeacon>();
        mb.rotor = rotor.transform; mb.beam = beam; mb.glow = glow; mb.lens = lens.GetComponent<Renderer>();
        EditorUtility.SetDirty(mb);
    }

    // ------------------------------------------------------------------ server racks (blue lights)

    static void BuildRacks(Transform parent)
    {
        Transform r = Group(parent, "ServerRacks");
        Material front = GetMat("M_RackFront", Lit());
        front.SetColor("_BaseColor", new Color(0.05f, 0.06f, 0.07f));
        front.SetFloat("_Metallic", 0.7f); front.SetFloat("_Smoothness", 0.55f);
        front.SetTexture("_EmissionMap", rackLeds);
        front.SetColor("_EmissionColor", Color.white * 2.2f);
        front.EnableKeyword("_EMISSION");
        front.globalIlluminationFlags = MaterialGlobalIlluminationFlags.None;
        EditorUtility.SetDirty(front);

        float[] zs = { -1.0f, 0.8f, 2.6f, 4.4f, 6.2f };
        for (int side = -1; side <= 1; side += 2)
        {
            float x = side * (HallW / 2f - 1.2f);
            foreach (float z in zs)
            {
                GameObject rack = new GameObject("Rack");
                rack.transform.SetParent(r, false);
                rack.transform.localPosition = new Vector3(x, 0, z);
                Transform t = rack.transform;

                Prim(PrimitiveType.Cube, "Body", t, new Vector3(0, 1.2f, 0), new Vector3(1.0f, 2.4f, 0.95f), Tiled(darkSteel, "M_RackBody", new Vector2(0.5f, 1f)));
                GameObject face = Prim(PrimitiveType.Cube, "Front", t, new Vector3(-side * 0.51f, 1.2f, 0), new Vector3(0.04f, 2.2f, 0.85f), front);
                Object.DestroyImmediate(face.GetComponent<Collider>());
                PulseGlow pg = face.AddComponent<PulseGlow>();
                pg.speed = 2.2f; pg.minFactor = 0.55f; pg.flicker = true; pg.emission = Color.white; pg.intensity = 2.2f;

                for (int i = 0; i < 5; i++)
                    Prim(PrimitiveType.Cube, "Vent", t, new Vector3(-side * 0.535f, 0.35f + i * 0.04f, 0), new Vector3(0.02f, 0.015f, 0.7f), blackMat);
                Prim(PrimitiveType.Cube, "Top", t, new Vector3(0, 2.42f, 0), new Vector3(1.04f, 0.05f, 1.0f), blackMat);
            }
            // blue light for each pair of racks
            NewLight("RackLight" + (side < 0 ? "L1" : "R1"), r, LightType.Point, BlueC, 4f, 6.5f, new Vector3(side * (HallW / 2f - 2.3f), 1.7f, 0.8f));
            NewLight("RackLight" + (side < 0 ? "L2" : "R2"), r, LightType.Point, BlueC, 4f, 6.5f, new Vector3(side * (HallW / 2f - 2.3f), 1.7f, 4.8f));
        }
    }

    // ------------------------------------------------------------------ ceiling (beams, real lamp prefabs)

    static void BuildCeiling(Transform parent)
    {
        Transform c = Group(parent, "CeilingDetails");
        for (float z = -7.5f; z <= 7.6f; z += 3f)
        {
            Prim(PrimitiveType.Cube, "Beam", c, new Vector3(0, HallH - 0.2f, z), new Vector3(HallW, 0.4f, 0.35f), darkSteel);
            Prim(PrimitiveType.Cube, "Beam_Lip", c, new Vector3(0, HallH - 0.43f, z), new Vector3(HallW, 0.06f, 0.6f), blackMat);
        }
        // cable trays
        Prim(PrimitiveType.Cube, "Tray_L", c, new Vector3(-2.4f, HallH - 0.12f, 0), new Vector3(0.5f, 0.1f, HallL), blackMat);
        Prim(PrimitiveType.Cube, "Tray_R", c, new Vector3(2.4f, HallH - 0.12f, 0), new Vector3(0.5f, 0.1f, HallL), blackMat);

        // lamp prefabs that already exist in the project (looked up, not hard-coded)
        string[] names = { "Classic_fluorescent_lamp", "Large_round_lamp", "Small_roof_lamp" };
        float[] zs = { -6f, -1.5f, 3f };
        for (int i = 0; i < names.Length; i++)
        {
            GameObject prefab = FindPrefab(names[i]);
            if (prefab == null) { Debug.LogWarning("Lamp prefab not found: " + names[i]); continue; }
            GameObject lamp = (GameObject)PrefabUtility.InstantiatePrefab(prefab, c);
            lamp.transform.position = new Vector3(0, HallH - 0.5f, zs[i]);
            NewLight("LampLight" + i, c, LightType.Point, new Color(0.75f, 0.88f, 1f), 3.5f, 9f, new Vector3(0, HallH - 1.2f, zs[i]));
        }
    }

    static GameObject FindPrefab(string name)
    {
        foreach (string guid in AssetDatabase.FindAssets(name + " t:Prefab"))
        {
            string p = AssetDatabase.GUIDToAssetPath(guid);
            if (Path.GetFileNameWithoutExtension(p) == name) return AssetDatabase.LoadAssetAtPath<GameObject>(p);
        }
        return null;
    }

    // ------------------------------------------------------------------ floor details

    static void BuildFloorDetails(Transform parent)
    {
        Transform f = Group(parent, "FloorDetails");

        // hazard stripes in front of the vault
        Material yellow = PbrMat("M_HazardYellow", new Color(0.8f, 0.55f, 0.05f), null, null, null, null, Vector2.one, 0f, 0.35f);
        for (int i = 0; i < 16; i++)
            Prim(PrimitiveType.Cube, "Hazard" + i, f, new Vector3(-3.75f + i * 0.5f, 0.01f, 6.4f), new Vector3(0.5f, 0.02f, 0.45f), i % 2 == 0 ? yellow : blackMat);

        // two guide lines of amber light leading to the vault
        Prim(PrimitiveType.Cube, "GuideL", f, new Vector3(-1.6f, 0.012f, 0f), new Vector3(0.05f, 0.02f, 12.5f), amberGlow);
        Prim(PrimitiveType.Cube, "GuideR", f, new Vector3(1.6f, 0.012f, 0f), new Vector3(0.05f, 0.02f, 12.5f), amberGlow);

        // soft puddle of amber light on the floor in front of the door
        GameObject spill = Prim(PrimitiveType.Quad, "LightSpill", f, new Vector3(0, 0.025f, 4.5f), new Vector3(7f, 7f, 1f), Additive("M_LightSpill", new Color(1f, 0.5f, 0.1f, 0.45f), softDot));
        spill.transform.rotation = Quaternion.Euler(90, 0, 0);
        Object.DestroyImmediate(spill.GetComponent<Collider>());

        // floor grates / drains
        for (int i = 0; i < 3; i++)
            Prim(PrimitiveType.Cube, "Grate" + i, f, new Vector3(-2.7f + i * 2.7f, 0.006f, -2.0f), new Vector3(1.6f, 0.012f, 0.5f), blackMat);
    }

    // ------------------------------------------------------------------ signs

    static void BuildSigns(Transform parent)
    {
        Transform s = Group(parent, "Signs");
        Color warn = new Color(1f, 0.6f, 0.15f);
        // left wall (readable looking toward -X), right wall (looking toward +X)
        Text("Sign_Restricted", s, "RESTRICTED AREA", 2.2f, new Vector3(-HallW / 2f + 0.43f, 3.0f, 1.0f), new Vector3(0, -90, 0), warn);
        Text("Sign_Sector", s, "SECTOR 07\nSYSTEM FAILURE", 1.6f, new Vector3(-HallW / 2f + 0.43f, 2.95f, 5.0f), new Vector3(0, -90, 0), new Color(0.4f, 0.75f, 1f));
        Text("Sign_Protocol", s, "PROTOCOL LAST", 2.2f, new Vector3(HallW / 2f - 0.43f, 3.0f, 1.0f), new Vector3(0, 90, 0), warn);
        Text("Sign_Test", s, "TEST EVERYTHING", 1.6f, new Vector3(HallW / 2f - 0.43f, 2.95f, 5.0f), new Vector3(0, 90, 0), new Color(0.4f, 0.75f, 1f));
        Text("Sign_Vault", s, "VAULT 07  -  SEALED", 2.4f, new Vector3(0, 0.55f, HallL / 2f - 0.08f), Vector3.zero, new Color(1f, 0.55f, 0.15f));
    }

    // ------------------------------------------------------------------ lights, dust, camera, atmosphere

    static void BuildLights(Transform parent)
    {
        Transform l = Group(parent, "FillLights");
        // very weak cool fill from behind the camera so shapes are readable
        NewLight("CoolFill", l, LightType.Point, new Color(0.45f, 0.6f, 0.9f), 1.8f, 14f, new Vector3(0, 4.2f, -6.5f));
        // amber rim from the vault down the hall
        NewLight("HallAmber", l, LightType.Point, Amber, 3.5f, 14f, new Vector3(0, 3.2f, 3.5f));
    }

    static void BuildDust(Transform parent)
    {
        GameObject go = new GameObject("DustParticles");
        go.transform.SetParent(parent, false);
        go.transform.localPosition = new Vector3(0, 3f, 0);
        ParticleSystem ps = go.AddComponent<ParticleSystem>();
        ps.Stop(true, ParticleSystemStopBehavior.StopEmittingAndClear);

        var main = ps.main;
        main.duration = 30f; main.loop = true; main.prewarm = true;
        main.startLifetime = new ParticleSystem.MinMaxCurve(10f, 18f);
        main.startSpeed = new ParticleSystem.MinMaxCurve(0.02f, 0.12f);
        main.startSize = new ParticleSystem.MinMaxCurve(0.03f, 0.09f);
        main.startColor = new Color(1f, 0.82f, 0.6f, 0.35f);
        main.maxParticles = 260;
        main.simulationSpace = ParticleSystemSimulationSpace.World;

        var emission = ps.emission; emission.rateOverTime = 18f;
        var shape = ps.shape; shape.shapeType = ParticleSystemShapeType.Box; shape.scale = new Vector3(HallW - 1f, 5.5f, HallL - 1f);
        var vel = ps.velocityOverLifetime; vel.enabled = true;
        vel.x = new ParticleSystem.MinMaxCurve(-0.05f, 0.05f); vel.y = new ParticleSystem.MinMaxCurve(0.0f, 0.06f); vel.z = new ParticleSystem.MinMaxCurve(-0.05f, 0.05f);
        var noise = ps.noise; noise.enabled = true; noise.strength = 0.15f; noise.frequency = 0.25f;
        var col = ps.colorOverLifetime; col.enabled = true;
        Gradient g = new Gradient();
        g.SetKeys(new[] { new GradientColorKey(Color.white, 0f), new GradientColorKey(Color.white, 1f) },
                  new[] { new GradientAlphaKey(0f, 0f), new GradientAlphaKey(1f, 0.2f), new GradientAlphaKey(1f, 0.8f), new GradientAlphaKey(0f, 1f) });
        col.color = g;

        ParticleSystemRenderer pr = go.GetComponent<ParticleSystemRenderer>();
        pr.sharedMaterial = Additive("M_Dust", new Color(1f, 0.85f, 0.65f, 0.7f), softDot, true);
        ps.Play();
    }

    static void BuildCamera()
    {
        GameObject go = new GameObject("Main Camera");
        go.tag = "MainCamera";
        Camera cam = go.AddComponent<Camera>();
        cam.clearFlags = CameraClearFlags.SolidColor;
        cam.backgroundColor = Color.black;
        cam.fieldOfView = 50f;
        cam.nearClipPlane = 0.1f; cam.farClipPlane = 60f;
        go.AddComponent<AudioListener>();

        UniversalAdditionalCameraData data = go.AddComponent<UniversalAdditionalCameraData>();
        data.renderPostProcessing = true;
        data.antialiasing = AntialiasingMode.SubpixelMorphologicalAntiAliasing;

        Vector3 pos = new Vector3(0f, 1.7f, -7.4f);
        go.transform.position = pos;
        go.transform.rotation = Quaternion.LookRotation((VaultCenter + new Vector3(0, 0.3f, 0)) - pos, Vector3.up);
        go.AddComponent<MenuCameraSway>();
    }

    static void BuildAtmosphere(Transform parent)
    {
        RenderSettings.skybox = null;
        RenderSettings.ambientMode = AmbientMode.Flat;
        RenderSettings.ambientLight = new Color(0.035f, 0.045f, 0.06f);
        RenderSettings.fog = true;
        RenderSettings.fogMode = FogMode.ExponentialSquared;
        RenderSettings.fogColor = new Color(0.04f, 0.05f, 0.065f);
        RenderSettings.fogDensity = 0.022f;

        // reflections so the glossy floor and steel pick up the amber and blue lights
        GameObject pg = new GameObject("ReflectionProbe");
        pg.transform.SetParent(parent, false);
        pg.transform.localPosition = new Vector3(0, 3f, 0);
        ReflectionProbe probe = pg.AddComponent<ReflectionProbe>();
        probe.mode = ReflectionProbeMode.Realtime;
        probe.refreshMode = ReflectionProbeRefreshMode.OnAwake;
        probe.timeSlicingMode = ReflectionProbeTimeSlicingMode.NoTimeSlicing;
        probe.size = new Vector3(HallW, HallH, HallL);
        probe.boxProjection = true;
        probe.resolution = 256;
        probe.intensity = 1.2f;
    }

    static void BuildPostProcessing()
    {
        try
        {
            string path = Generated + "/MenuVolumeProfile.asset";
            AssetDatabase.DeleteAsset(path);
            VolumeProfile profile = ScriptableObject.CreateInstance<VolumeProfile>();
            AssetDatabase.CreateAsset(profile, path);

            var bloom = profile.Add<Bloom>(true);
            bloom.threshold.Override(0.9f); bloom.intensity.Override(1.6f); bloom.scatter.Override(0.7f);
            var vig = profile.Add<Vignette>(true);
            vig.intensity.Override(0.42f); vig.smoothness.Override(0.5f);
            var tone = profile.Add<Tonemapping>(true);
            tone.mode.Override(TonemappingMode.ACES);
            var ca = profile.Add<ColorAdjustments>(true);
            ca.postExposure.Override(-0.1f); ca.contrast.Override(18f); ca.saturation.Override(-6f);
            var grain = profile.Add<FilmGrain>(true);
            grain.type.Override(FilmGrainLookup.Medium3); grain.intensity.Override(0.22f);
            var chroma = profile.Add<ChromaticAberration>(true);
            chroma.intensity.Override(0.08f);

            foreach (VolumeComponent comp in profile.components)
            {
                comp.name = comp.GetType().Name;
                AssetDatabase.AddObjectToAsset(comp, profile);
            }
            EditorUtility.SetDirty(profile);

            GameObject vg = new GameObject("MenuPostProcessing");
            Volume vol = vg.AddComponent<Volume>();
            vol.isGlobal = true; vol.priority = 10f; vol.sharedProfile = profile;
        }
        catch (System.Exception ex)
        {
            Debug.LogWarning("Post-processing could not be added (the scene still works): " + ex.Message);
        }
    }

    // ------------------------------------------------------------------ small helpers

    static void EnsureFolder(string parent, string child)
    {
        if (!AssetDatabase.IsValidFolder(parent + "/" + child)) AssetDatabase.CreateFolder(parent, child);
    }

    static Transform Group(Transform parent, string name)
    {
        GameObject g = new GameObject(name);
        g.transform.SetParent(parent, false);
        return g.transform;
    }

    static GameObject Prim(PrimitiveType type, string name, Transform parent, Vector3 localPos, Vector3 scale, Material mat)
    {
        GameObject go = GameObject.CreatePrimitive(type);
        go.name = name;
        go.transform.SetParent(parent, false);
        go.transform.localPosition = localPos;
        go.transform.localScale = scale;
        if (mat != null) go.GetComponent<Renderer>().sharedMaterial = mat;
        return go;
    }

    // cylinder whose circular face looks along Z when euler = (90,0,0)
    static GameObject Cyl(string name, Transform parent, Vector3 localPos, Vector3 scale, Material mat, Vector3 euler)
    {
        GameObject go = Prim(PrimitiveType.Cylinder, name, parent, localPos, scale, mat);
        go.transform.localEulerAngles = euler;
        return go;
    }

    static GameObject MeshObj(string name, Transform parent, Mesh mesh, Vector3 localPos, Material mat)
    {
        GameObject go = new GameObject(name);
        go.transform.SetParent(parent, false);
        go.transform.localPosition = localPos;
        go.AddComponent<MeshFilter>().sharedMesh = mesh;
        go.AddComponent<MeshRenderer>().sharedMaterial = mat;
        return go;
    }

    static Light NewLight(string name, Transform parent, LightType type, Color color, float intensity, float range, Vector3 localPos)
    {
        GameObject go = new GameObject(name);
        go.transform.SetParent(parent, false);
        go.transform.localPosition = localPos;
        Light l = go.AddComponent<Light>();
        l.type = type; l.color = color; l.intensity = intensity; l.range = range;
        l.shadows = LightShadows.None;
        return l;
    }

    static TextMeshPro Text(string name, Transform parent, string text, float size, Vector3 localPos, Vector3 euler, Color color)
    {
        GameObject go = new GameObject(name);
        go.transform.SetParent(parent, false);
        go.transform.localPosition = localPos;
        go.transform.localEulerAngles = euler;
        TextMeshPro t = go.AddComponent<TextMeshPro>();
        t.text = text; t.fontSize = size; t.color = color;
        t.alignment = TextAlignmentOptions.Center;
        t.rectTransform.sizeDelta = new Vector2(8f, 2f);
        return t;
    }

    // A torus (donut) lying in the XY plane, facing -Z. Saved as an asset so the scene keeps it.
    static Mesh Torus(string name, float R, float r, int seg, int sides)
    {
        string path = Generated + "/" + name + ".asset";
        AssetDatabase.DeleteAsset(path);

        Vector3[] vs = new Vector3[(seg + 1) * (sides + 1)];
        Vector3[] ns = new Vector3[vs.Length];
        Vector2[] uv = new Vector2[vs.Length];
        for (int i = 0; i <= seg; i++)
        {
            float a = (float)i / seg * Mathf.PI * 2f;
            Vector3 center = new Vector3(Mathf.Cos(a) * R, Mathf.Sin(a) * R, 0f);
            for (int j = 0; j <= sides; j++)
            {
                float b = (float)j / sides * Mathf.PI * 2f;
                Vector3 n = new Vector3(Mathf.Cos(b) * Mathf.Cos(a), Mathf.Cos(b) * Mathf.Sin(a), Mathf.Sin(b));
                int k = i * (sides + 1) + j;
                vs[k] = center + n * r; ns[k] = n; uv[k] = new Vector2((float)i / seg * 8f, (float)j / sides);
            }
        }
        int[] tris = new int[seg * sides * 6];
        int ti = 0;
        for (int i = 0; i < seg; i++)
            for (int j = 0; j < sides; j++)
            {
                int a0 = i * (sides + 1) + j, b0 = a0 + sides + 1;
                tris[ti++] = a0; tris[ti++] = b0; tris[ti++] = a0 + 1;
                tris[ti++] = b0; tris[ti++] = b0 + 1; tris[ti++] = a0 + 1;
            }
        Mesh m = new Mesh { name = name, vertices = vs, normals = ns, uv = uv, triangles = tris };
        m.RecalculateBounds();
        m.RecalculateTangents();
        AssetDatabase.CreateAsset(m, path);
        return m;
    }
}
#endif
