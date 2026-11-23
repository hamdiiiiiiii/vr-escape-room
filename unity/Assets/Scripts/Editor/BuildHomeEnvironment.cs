#if UNITY_EDITOR
using System.Collections.Generic;
using System.IO;
using TMPro;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.SceneManagement;

// Menu: Museum > Build Home Environment
// Builds the lobby as a sealed bunker / escape room: big round vault door with a glowing ring,
// entry keypad box and wall monitor beside it, a central platform with three consoles,
// server racks, lockers, desk, clue board, alarms, pipes, lighting, reflections and a few flying bugs.
// Run "Museum > Build Home Menu" first, then this. Safe to run again: it replaces its own objects.
public static class BuildHomeEnvironment
{
    const string Folder = "Assets/HomeTheme";
    const string MatFolder = "Assets/HomeTheme/Materials";

    static readonly Color Teal = new Color32(63, 224, 197, 255);
    static readonly Color Orange = new Color32(255, 138, 61, 255);
    static readonly Color Green = new Color32(74, 222, 128, 255);
    static readonly Color Red = new Color32(255, 70, 70, 255);
    static readonly Color Blue = new Color32(70, 150, 255, 255);

    static Dictionary<string, Material> cache = new Dictionary<string, Material>();

    // Must match BuildHomeMenu
    static readonly Vector3 MonitorPos = new Vector3(-3.6f, 2.0f, 4.78f);
    static readonly Vector3 KeypadPos = new Vector3(3.4f, 1.4f, 4.75f);

    [MenuItem("Museum/Build Home Environment")]
    static void Build()
    {
        cache.Clear();
        EnsureFolders();
        Random.InitState(7);

        DestroyIfExists("HomeEnvironment");
        DestroyIfExists("HomeVolume");

        GameObject rootGo = new GameObject("HomeEnvironment");
        Undo.RegisterCreatedObjectUndo(rootGo, "Build Home Environment");
        Transform root = rootGo.transform;

        DressRoom(root);
        VaultDoor(root);
        KeypadBox(root);
        MonitorFrame(root);
        Platform(root);
        ServerRacks(root);
        Desk(root);
        Lockers(root);
        ClueBoard(root);
        Crates(root);
        WetFloorSign(root);
        Alarms(root);
        Pipes(root);
        CeilingLights(root);
        Signs(root);
        BugSwarm(root);
        Atmosphere(root);
        PostProcessing();

        LinkMenu();

        AssetDatabase.SaveAssets();
        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        Selection.activeGameObject = rootGo;
        Debug.Log("Home environment built. Press Play. Run 'Museum > Build Home Environment' again to rebuild it.");
    }

    static void LinkMenu()
    {
        HomeMenu menu = Object.FindFirstObjectByType<HomeMenu>();
        VaultDoorController vault = Object.FindFirstObjectByType<VaultDoorController>();
        if (menu == null || vault == null) return;
        SerializedObject so = new SerializedObject(menu);
        SerializedProperty p = so.FindProperty("vaultDoor");
        if (p != null) { p.objectReferenceValue = vault; so.ApplyModifiedProperties(); }
    }

    // ---------- Room shell ----------

    static void DressRoom(Transform root)
    {
        Material wall = Mat("Wall", new Color32(34, 44, 52, 255), null, 0f, 0.35f, 0.45f);
        Material ceiling = Mat("Ceiling", new Color32(14, 20, 26, 255), null, 0f, 0.2f, 0.3f);
        Material floor = Mat("Floor", Color.white, null, 0f, 0.1f, 0.9f);

        Texture2D tex = MakeConcreteTexture();
        if (tex != null)
        {
            floor.SetTexture("_BaseMap", tex);
            floor.SetTextureScale("_BaseMap", new Vector2(12f, 10f));
            floor.mainTexture = tex;
            floor.mainTextureScale = new Vector2(12f, 10f);
        }

        Paint("Floor", floor);
        Paint("Wall_Back", wall);
        Paint("Wall_Front", wall);
        Paint("Wall_Left", wall);
        Paint("Wall_Right", wall);

        Prim(PrimitiveType.Cube, "Ceiling", root, new Vector3(0f, 3.55f, 0f), new Vector3(12f, 0.1f, 10f), ceiling, false);

        // wall panel seams and dark skirting so the walls are not flat
        Material seam = Mat("Seam", new Color32(10, 14, 18, 255), null, 0f, 0.5f, 0.4f);
        for (int i = -2; i <= 2; i++)
        {
            Prim(PrimitiveType.Cube, "SeamBack" + i, root, new Vector3(i * 2.4f, 1.7f, 4.88f), new Vector3(0.04f, 3.4f, 0.03f), seam, false);
            Prim(PrimitiveType.Cube, "SeamFront" + i, root, new Vector3(i * 2.4f, 1.7f, -4.88f), new Vector3(0.04f, 3.4f, 0.03f), seam, false);
        }
        for (int i = -3; i <= 3; i++)
        {
            Prim(PrimitiveType.Cube, "SeamL" + i, root, new Vector3(-5.88f, 1.7f, i * 1.4f), new Vector3(0.03f, 3.4f, 0.04f), seam, false);
            Prim(PrimitiveType.Cube, "SeamR" + i, root, new Vector3(5.88f, 1.7f, i * 1.4f), new Vector3(0.03f, 3.4f, 0.04f), seam, false);
        }
        Material skirt = Mat("Skirt", new Color32(16, 20, 24, 255), null, 0f, 0.6f, 0.4f);
        Prim(PrimitiveType.Cube, "SkirtBack", root, new Vector3(0f, 0.15f, 4.86f), new Vector3(11.8f, 0.3f, 0.06f), skirt, false);
        Prim(PrimitiveType.Cube, "SkirtFront", root, new Vector3(0f, 0.15f, -4.86f), new Vector3(11.8f, 0.3f, 0.06f), skirt, false);
        Prim(PrimitiveType.Cube, "SkirtLeft", root, new Vector3(-5.86f, 0.15f, 0f), new Vector3(0.06f, 0.3f, 9.8f), skirt, false);
        Prim(PrimitiveType.Cube, "SkirtRight", root, new Vector3(5.86f, 0.15f, 0f), new Vector3(0.06f, 0.3f, 9.8f), skirt, false);

        // hazard stripe along the base of the back wall
        Material orange = Glow("HazardOrange", Orange, 0.8f);
        Material black = Mat("HazardBlack", new Color32(8, 8, 8, 255), null, 0f, 0.2f, 0.4f);
        for (int i = 0; i < 24; i++)
            Prim(PrimitiveType.Cube, "HazardStripe" + i, root, new Vector3(-5.75f + i * 0.5f, 0.012f, 4.55f), new Vector3(0.5f, 0.02f, 0.5f), i % 2 == 0 ? orange : black, false);
    }

    static void Paint(string objectName, Material m)
    {
        GameObject go = GameObject.Find(objectName);
        if (go == null) { Debug.LogWarning("Home environment: '" + objectName + "' not found (build the walls first)."); return; }
        Renderer r = go.GetComponent<Renderer>();
        if (r != null) { Undo.RecordObject(r, "Paint"); r.sharedMaterial = m; }
    }

    // 1 m concrete tile with a seam line and noise
    static Texture2D MakeConcreteTexture()
    {
        string assetPath = Folder + "/ConcreteFloor.png";
        int n = 256;
        Texture2D t = new Texture2D(n, n, TextureFormat.RGBA32, false);
        for (int y = 0; y < n; y++)
        {
            for (int x = 0; x < n; x++)
            {
                float v = 0.20f + Mathf.PerlinNoise(x * 0.05f, y * 0.05f) * 0.10f + Mathf.PerlinNoise(x * 0.4f, y * 0.4f) * 0.04f;
                if (x < 2 || y < 2) v *= 0.45f;      // seam
                t.SetPixel(x, y, new Color(v * 0.9f, v, v * 1.05f, 1f));
            }
        }
        t.Apply();
        File.WriteAllBytes(Path.Combine(Application.dataPath, "HomeTheme/ConcreteFloor.png"), t.EncodeToPNG());
        Object.DestroyImmediate(t);
        AssetDatabase.ImportAsset(assetPath, ImportAssetOptions.ForceUpdate);
        return AssetDatabase.LoadAssetAtPath<Texture2D>(assetPath);
    }

    // ---------- The vault door ----------
    // Layout (local): center of the round door is (0,0). The door swings open on a hinge at its left edge.

    static void VaultDoor(Transform root)
    {
        GameObject vault = new GameObject("VaultDoor");
        vault.transform.SetParent(root, false);
        vault.transform.localPosition = new Vector3(0f, 1.55f, 4.7f);
        Transform v = vault.transform;
        VaultDoorController ctrl = vault.AddComponent<VaultDoorController>();

        Material steel = Mat("VaultSteel", new Color32(70, 82, 92, 255), null, 0f, 0.95f, 0.55f);
        Material dark = Mat("VaultDark", new Color32(20, 26, 32, 255), null, 0f, 0.8f, 0.45f);
        Material bolt = Mat("VaultBolt", new Color32(140, 152, 164, 255), null, 0f, 1f, 0.7f);
        Material orange = Glow("NeonOrange", Orange, 3.5f);
        Material warm = Glow("WarmBar", new Color32(255, 200, 130, 255), 3f);
        Material red = Glow("VaultRed", Red, 4f);
        Material portal = Glow("Portal", new Color32(190, 255, 220, 255), 3f);

        // frame
        Prim(PrimitiveType.Cube, "FrameTop", v, new Vector3(0f, 1.85f, 0f), new Vector3(4.1f, 0.4f, 0.4f), dark, false);
        Prim(PrimitiveType.Cube, "FrameBottom", v, new Vector3(0f, -1.45f, 0f), new Vector3(4.1f, 0.2f, 0.4f), dark, false);
        Prim(PrimitiveType.Cube, "FrameLeft", v, new Vector3(-2.0f, 0.2f, 0f), new Vector3(0.4f, 3.7f, 0.4f), dark, false);
        Prim(PrimitiveType.Cube, "FrameRight", v, new Vector3(2.0f, 0.2f, 0f), new Vector3(0.4f, 3.7f, 0.4f), dark, false);

        // warm vertical light bars on both sides of the frame
        Prim(PrimitiveType.Cube, "LightBarL", v, new Vector3(-2.25f, 0.2f, -0.15f), new Vector3(0.07f, 3.2f, 0.05f), warm, false);
        Prim(PrimitiveType.Cube, "LightBarR", v, new Vector3(2.25f, 0.2f, -0.15f), new Vector3(0.07f, 3.2f, 0.05f), warm, false);

        // glowing orange halo ring behind the door disk
        GameObject halo = Prim(PrimitiveType.Cylinder, "GlowRing", v, new Vector3(0f, 0f, -0.1f), new Vector3(3.3f, 0.03f, 3.3f), orange, false);
        halo.transform.localEulerAngles = new Vector3(90f, 0f, 0f);
        GameObject ringBase = Prim(PrimitiveType.Cylinder, "RingBase", v, new Vector3(0f, 0f, -0.05f), new Vector3(3.5f, 0.03f, 3.5f), dark, false);
        ringBase.transform.localEulerAngles = new Vector3(90f, 0f, 0f);

        // what is revealed when the door opens
        GameObject behind = new GameObject("BehindDoor");
        behind.transform.SetParent(v, false);
        GameObject pDisk = Prim(PrimitiveType.Cylinder, "Portal", behind.transform, new Vector3(0f, 0f, 0.12f), new Vector3(2.95f, 0.02f, 2.95f), portal, false);
        pDisk.transform.localEulerAngles = new Vector3(90f, 0f, 0f);
        GameObject pl = new GameObject("PortalLight");
        pl.transform.SetParent(behind.transform, false);
        pl.transform.localPosition = new Vector3(0f, 0f, -0.6f);
        Light light = pl.AddComponent<Light>();
        light.type = LightType.Point; light.color = new Color(0.6f, 1f, 0.8f); light.intensity = 6f; light.range = 8f;
        behind.SetActive(false);

        // the door itself: pivot on the left edge (x = -1.5)
        GameObject assembly = new GameObject("DoorAssembly");
        assembly.transform.SetParent(v, false);
        assembly.transform.localPosition = new Vector3(-1.5f, 0f, -0.2f);
        Transform a = assembly.transform;

        GameObject disk = Prim(PrimitiveType.Cylinder, "DoorDisk", a, new Vector3(1.5f, 0f, 0f), new Vector3(3.0f, 0.1f, 3.0f), steel, false);
        disk.transform.localEulerAngles = new Vector3(90f, 0f, 0f);
        GameObject rim = Prim(PrimitiveType.Cylinder, "DoorRim", a, new Vector3(1.5f, 0f, -0.1f), new Vector3(2.55f, 0.04f, 2.55f), dark, false);
        rim.transform.localEulerAngles = new Vector3(90f, 0f, 0f);
        GameObject plate = Prim(PrimitiveType.Cylinder, "DoorPlate", a, new Vector3(1.5f, 0f, -0.16f), new Vector3(2.4f, 0.03f, 2.4f), steel, false);
        plate.transform.localEulerAngles = new Vector3(90f, 0f, 0f);

        for (int i = 0; i < 12; i++)
        {
            float ang = i * Mathf.PI * 2f / 12f;
            GameObject b = Prim(PrimitiveType.Cylinder, "Bolt" + i, a, new Vector3(1.5f + Mathf.Cos(ang) * 1.32f, Mathf.Sin(ang) * 1.32f, -0.2f), new Vector3(0.2f, 0.05f, 0.2f), bolt, false);
            b.transform.localEulerAngles = new Vector3(90f, 0f, 0f);
        }

        // two horizontal locking bars across the door
        Prim(PrimitiveType.Cube, "LockBarTop", a, new Vector3(1.5f, 0.82f, -0.22f), new Vector3(1.9f, 0.14f, 0.08f), dark, false);
        Prim(PrimitiveType.Cube, "LockBarBottom", a, new Vector3(1.5f, -0.82f, -0.22f), new Vector3(1.9f, 0.14f, 0.08f), dark, false);

        // wheel
        GameObject wheel = new GameObject("Wheel");
        wheel.transform.SetParent(a, false);
        wheel.transform.localPosition = new Vector3(1.5f, 0f, -0.3f);
        GameObject hub = Prim(PrimitiveType.Cylinder, "Hub", wheel.transform, Vector3.zero, new Vector3(0.45f, 0.08f, 0.45f), bolt, false);
        hub.transform.localEulerAngles = new Vector3(90f, 0f, 0f);
        for (int i = 0; i < 4; i++)
        {
            GameObject spoke = Prim(PrimitiveType.Cube, "Spoke" + i, wheel.transform, new Vector3(0f, 0f, -0.04f), new Vector3(1.3f, 0.09f, 0.07f), bolt, false);
            spoke.transform.localEulerAngles = new Vector3(0f, 0f, i * 45f);
        }
        GameObject wRing = Prim(PrimitiveType.Cylinder, "WheelRing", wheel.transform, new Vector3(0f, 0f, -0.02f), new Vector3(1.25f, 0.03f, 1.25f), dark, false);
        wRing.transform.localEulerAngles = new Vector3(90f, 0f, 0f);
        wRing.transform.localScale = new Vector3(1.25f, 0.02f, 1.25f);

        // status lamp on the door (red pulsing = locked)
        GameObject lamp = Prim(PrimitiveType.Sphere, "StatusLamp", a, new Vector3(1.5f, 1.12f, -0.3f), new Vector3(0.2f, 0.2f, 0.2f), red, false);
        PulseGlow pg = lamp.AddComponent<PulseGlow>();
        pg.speed = 3f; pg.minFactor = 0.2f; pg.emission = Red; pg.intensity = 4f;

        WText("DoorLabel", a, "VAULT 07", 1.5f, new Vector3(1.5f, -1.12f, -0.27f), new Vector3(0f, 0f, 0f), Orange);

        // connect the controller
        SerializedObject so = new SerializedObject(ctrl);
        SetRef(so, "doorAssembly", a);
        SetRef(so, "wheel", wheel.transform);
        SetRef(so, "lamp", lamp.GetComponent<Renderer>());
        SetRef(so, "lightBehind", behind);
        so.ApplyModifiedProperties();

        // red alarm wash from above the door
        GameObject wash = new GameObject("DoorWash");
        wash.transform.SetParent(v, false);
        wash.transform.localPosition = new Vector3(0f, 0.5f, -2.2f);
        Light w = wash.AddComponent<Light>();
        w.type = LightType.Point; w.color = new Color(1f, 0.55f, 0.25f); w.intensity = 4f; w.range = 8f;
    }

    static void SetRef(SerializedObject so, string field, Object value)
    {
        SerializedProperty p = so.FindProperty(field);
        if (p == null) { Debug.LogWarning("VaultDoorController has no field " + field); return; }
        p.objectReferenceValue = value;
    }

    // ---------- The keypad box on the wall (the KeypadCanvas sits on its face) ----------

    static void KeypadBox(Transform root)
    {
        Material body = Mat("KeypadBody", new Color32(26, 32, 38, 255), null, 0f, 0.85f, 0.5f);
        Material orange = Glow("NeonOrange", Orange, 3.5f);
        Material green = Glow("KeyGreen", Green, 3f);
        Material redM = Glow("VaultRed", Red, 4f);

        Vector3 p = new Vector3(KeypadPos.x, KeypadPos.y, 4.82f);
        Prim(PrimitiveType.Cube, "KeypadHousing", root, p, new Vector3(0.84f, 1.3f, 0.12f), body, false);

        // orange bezel around the face
        float zf = 4.755f;
        Prim(PrimitiveType.Cube, "BezelTop", root, new Vector3(p.x, p.y + 0.67f, zf), new Vector3(0.84f, 0.03f, 0.03f), orange, false);
        Prim(PrimitiveType.Cube, "BezelBottom", root, new Vector3(p.x, p.y - 0.67f, zf), new Vector3(0.84f, 0.03f, 0.03f), orange, false);
        Prim(PrimitiveType.Cube, "BezelL", root, new Vector3(p.x - 0.43f, p.y, zf), new Vector3(0.03f, 1.34f, 0.03f), orange, false);
        Prim(PrimitiveType.Cube, "BezelR", root, new Vector3(p.x + 0.43f, p.y, zf), new Vector3(0.03f, 1.34f, 0.03f), orange, false);

        // status lights and a card slot above the pad
        GameObject r = Prim(PrimitiveType.Sphere, "KeypadRed", root, new Vector3(p.x - 0.18f, p.y + 0.82f, 4.76f), new Vector3(0.09f, 0.09f, 0.09f), redM, false);
        PulseGlow pr = r.AddComponent<PulseGlow>(); pr.speed = 3f; pr.minFactor = 0.2f; pr.emission = Red; pr.intensity = 4f;
        Prim(PrimitiveType.Sphere, "KeypadGreen", root, new Vector3(p.x + 0.18f, p.y + 0.82f, 4.76f), new Vector3(0.09f, 0.09f, 0.09f), Mat("KeyGreenDim", new Color32(10, 40, 20, 255), null, 0f, 0f, 0.3f), false);
        Prim(PrimitiveType.Cube, "CardSlot", root, new Vector3(p.x, p.y + 0.82f, 4.76f), new Vector3(0.25f, 0.03f, 0.02f), orange, false);

        // mounting bracket / conduit up to the ceiling
        Prim(PrimitiveType.Cylinder, "Conduit", root, new Vector3(p.x, 2.6f, 4.84f), new Vector3(0.06f, 0.95f, 0.06f), Mat("Pipe", new Color32(40, 52, 64, 255), null, 0f, 0.9f, 0.6f), false);
        WText("KeypadSign", root, "ENTRY CODE", 1.3f, new Vector3(p.x, p.y + 1.0f, 4.78f), Vector3.zero, Orange);
    }

    static void MonitorFrame(Transform root)
    {
        Material body = Mat("KeypadBody", new Color32(26, 32, 38, 255), null, 0f, 0.85f, 0.5f);
        Material teal = Glow("NeonTeal", Teal, 3f);
        Prim(PrimitiveType.Cube, "MonitorFrame", root, new Vector3(MonitorPos.x, MonitorPos.y, 4.84f), new Vector3(2.6f, 1.6f, 0.06f), body, false);
        Prim(PrimitiveType.Cube, "MonitorGlowBottom", root, new Vector3(MonitorPos.x, MonitorPos.y - 0.82f, 4.8f), new Vector3(2.6f, 0.03f, 0.03f), teal, false);
        Prim(PrimitiveType.Cube, "MonitorShelf", root, new Vector3(MonitorPos.x, 1.0f, 4.7f), new Vector3(2.6f, 0.08f, 0.4f), body, false);
        // light on the monitor
        GameObject l = new GameObject("MonitorLight");
        l.transform.SetParent(root, false);
        l.transform.localPosition = new Vector3(MonitorPos.x, MonitorPos.y, 3.6f);
        Light li = l.AddComponent<Light>();
        li.type = LightType.Point; li.color = Teal; li.intensity = 1.6f; li.range = 4f;
    }

    // ---------- Central platform with three consoles ----------

    static void Platform(Transform root)
    {
        GameObject plat = new GameObject("Platform");
        plat.transform.SetParent(root, false);
        plat.transform.localPosition = new Vector3(0f, 0f, -0.2f);
        Transform p = plat.transform;

        Material steel = Mat("PlatformSteel", new Color32(46, 56, 64, 255), null, 0f, 0.9f, 0.6f);
        Material dark = Mat("VaultDark", new Color32(20, 26, 32, 255), null, 0f, 0.8f, 0.45f);
        Material orange = Glow("NeonOrange", Orange, 3.5f);
        Material panel = Glow("ConsolePanel", Orange, 1.6f);
        Material screen = Glow("ConsoleScreen", Blue, 2.2f);

        Prim(PrimitiveType.Cylinder, "RimGlow", p, new Vector3(0f, 0.04f, 0f), new Vector3(4.0f, 0.04f, 4.0f), orange, false);
        Prim(PrimitiveType.Cylinder, "Base", p, new Vector3(0f, 0.09f, 0f), new Vector3(3.85f, 0.05f, 3.85f), dark, false);
        Prim(PrimitiveType.Cylinder, "Top", p, new Vector3(0f, 0.15f, 0f), new Vector3(3.5f, 0.02f, 3.5f), steel, false);
        Prim(PrimitiveType.Cylinder, "InnerRing", p, new Vector3(0f, 0.175f, 0f), new Vector3(1.4f, 0.01f, 1.4f), dark, false);
        Prim(PrimitiveType.Cylinder, "InnerGlow", p, new Vector3(0f, 0.185f, 0f), new Vector3(1.0f, 0.01f, 1.0f), orange, false);

        string[] labels = { "A", "B", "C" };
        float[] angles = { -50f, 0f, 50f };
        for (int i = 0; i < 3; i++)
        {
            float a = angles[i] * Mathf.Deg2Rad;
            Vector3 dir = new Vector3(Mathf.Sin(a), 0f, Mathf.Cos(a));
            GameObject c = new GameObject("Console_" + labels[i]);
            c.transform.SetParent(p, false);
            c.transform.localPosition = dir * 1.25f + new Vector3(0f, 0.17f, 0f);
            c.transform.localRotation = Quaternion.LookRotation(dir, Vector3.up);   // front (-Z) faces the middle
            Transform t = c.transform;

            Prim(PrimitiveType.Cube, "Body", t, new Vector3(0f, 0.4f, 0f), new Vector3(0.75f, 0.8f, 0.45f), steel, true);
            GameObject top = Prim(PrimitiveType.Cube, "SlantTop", t, new Vector3(0f, 0.86f, -0.04f), new Vector3(0.72f, 0.06f, 0.4f), dark, false);
            top.transform.localEulerAngles = new Vector3(-28f, 0f, 0f);
            GameObject scr = Prim(PrimitiveType.Cube, "Screen", t, new Vector3(0f, 0.87f, -0.045f), new Vector3(0.62f, 0.02f, 0.3f), screen, false);
            scr.transform.localEulerAngles = new Vector3(-28f, 0f, 0f);
            Prim(PrimitiveType.Cube, "OrangePanel", t, new Vector3(0f, 0.45f, -0.23f), new Vector3(0.55f, 0.3f, 0.02f), panel, false);
            Prim(PrimitiveType.Cube, "TrimL", t, new Vector3(-0.38f, 0.4f, -0.22f), new Vector3(0.03f, 0.8f, 0.03f), orange, false);
            Prim(PrimitiveType.Cube, "TrimR", t, new Vector3(0.38f, 0.4f, -0.22f), new Vector3(0.03f, 0.8f, 0.03f), orange, false);
            WText("Label", t, labels[i], 2.2f, new Vector3(0f, 0.45f, -0.245f), new Vector3(0f, 180f, 0f), new Color32(25, 12, 0, 255));
        }

        // spotlights from above that light the platform
        for (int i = 0; i < 3; i++)
        {
            GameObject s = new GameObject("PlatformSpot" + i);
            s.transform.SetParent(root, false);
            s.transform.localPosition = new Vector3((i - 1) * 1.4f, 3.3f, -0.2f);
            s.transform.localEulerAngles = new Vector3(90f, 0f, 0f);
            Light l = s.AddComponent<Light>();
            l.type = LightType.Spot; l.spotAngle = 60f; l.range = 6f; l.intensity = 6f;
            l.color = new Color(1f, 0.82f, 0.6f);
        }
    }

    // ---------- Props ----------

    static void ServerRacks(Transform root)
    {
        Material rack = Mat("Rack", new Color32(22, 30, 38, 255), null, 0f, 0.8f, 0.5f);
        Material face = Mat("RackFace", new Color32(10, 14, 18, 255), null, 0f, 0.6f, 0.4f);
        Color[] leds = { Blue, Teal, Teal, Green, Blue };
        float[] zs = { -3.2f, -2.1f, -1.0f, 0.1f };
        int ledIndex = 0;

        foreach (float z in zs)
        {
            GameObject r = new GameObject("ServerRack_" + z);
            r.transform.SetParent(root, false);
            r.transform.localPosition = new Vector3(-5.3f, 0f, z);
            Prim(PrimitiveType.Cube, "Body", r.transform, new Vector3(0f, 1.2f, 0f), new Vector3(0.75f, 2.4f, 0.95f), rack, true);
            Prim(PrimitiveType.Cube, "Face", r.transform, new Vector3(0.38f, 1.2f, 0f), new Vector3(0.02f, 2.2f, 0.85f), face, false);

            for (int row = 0; row < 11; row++)
            {
                Prim(PrimitiveType.Cube, "Unit" + row, r.transform, new Vector3(0.395f, 0.3f + row * 0.19f, 0f), new Vector3(0.01f, 0.015f, 0.85f), rack, false);
                for (int col = 0; col < 3; col++)
                {
                    Color c = leds[(ledIndex++) % leds.Length];
                    Material led = Glow("Led_" + ColorKey(c), c, 3f);
                    GameObject led3 = Prim(PrimitiveType.Cube, "Led", r.transform, new Vector3(0.4f, 0.37f + row * 0.19f, -0.32f + col * 0.1f), new Vector3(0.02f, 0.04f, 0.04f), led, false);
                    PulseGlow pgl = led3.AddComponent<PulseGlow>();
                    pgl.speed = Random.Range(1.5f, 6f); pgl.minFactor = 0.1f; pgl.flicker = Random.value > 0.5f;
                    pgl.emission = c; pgl.intensity = 3f;
                }
            }
        }
    }

    static void Desk(Transform root)
    {
        Material body = Mat("DeskBody", new Color32(30, 38, 46, 255), null, 0f, 0.6f, 0.5f);
        Material screen = Glow("DeskScreen", Blue, 2.4f);
        Material teal = Glow("NeonTeal", Teal, 3f);

        GameObject d = new GameObject("TerminalDesk");
        d.transform.SetParent(root, false);
        d.transform.localPosition = new Vector3(-5.1f, 0f, 2.6f);
        Transform t = d.transform;

        Prim(PrimitiveType.Cube, "Top", t, new Vector3(0f, 0.8f, 0f), new Vector3(0.8f, 0.05f, 2.0f), body, true);
        Prim(PrimitiveType.Cube, "Leg1", t, new Vector3(0.3f, 0.4f, -0.9f), new Vector3(0.06f, 0.8f, 0.06f), body, false);
        Prim(PrimitiveType.Cube, "Leg2", t, new Vector3(0.3f, 0.4f, 0.9f), new Vector3(0.06f, 0.8f, 0.06f), body, false);
        Prim(PrimitiveType.Cube, "Drawers", t, new Vector3(-0.1f, 0.4f, 0.55f), new Vector3(0.5f, 0.75f, 0.7f), body, false);

        for (int i = 0; i < 2; i++)
        {
            float z = -0.5f + i * 0.9f;
            Prim(PrimitiveType.Cube, "MonitorBody" + i, t, new Vector3(-0.1f, 1.12f, z), new Vector3(0.05f, 0.5f, 0.8f), Mat("KeypadBody", new Color32(26, 32, 38, 255), null, 0f, 0.85f, 0.5f), false);
            Prim(PrimitiveType.Cube, "MonitorScreen" + i, t, new Vector3(-0.07f, 1.12f, z), new Vector3(0.02f, 0.44f, 0.72f), screen, false);
            Prim(PrimitiveType.Cube, "MonitorStand" + i, t, new Vector3(-0.1f, 0.9f, z), new Vector3(0.06f, 0.15f, 0.06f), body, false);
        }
        WText("DeskText", t, "> RUN TESTS\n> 13 BUGS FOUND\n> CODE LOCKED", 1.0f, new Vector3(-0.065f, 1.12f, -0.5f), new Vector3(0f, -90f, 0f), Teal);
        Prim(PrimitiveType.Cube, "DeskGlow", t, new Vector3(0.4f, 0.8f, 0f), new Vector3(0.02f, 0.03f, 2.0f), teal, false);

        // chair
        Prim(PrimitiveType.Cube, "ChairSeat", t, new Vector3(0.9f, 0.45f, -0.5f), new Vector3(0.5f, 0.06f, 0.5f), body, false);
        Prim(PrimitiveType.Cube, "ChairBack", t, new Vector3(1.13f, 0.8f, -0.5f), new Vector3(0.06f, 0.7f, 0.5f), body, false);
        Prim(PrimitiveType.Cylinder, "ChairPost", t, new Vector3(0.9f, 0.22f, -0.5f), new Vector3(0.07f, 0.22f, 0.07f), body, false);
    }

    static void Lockers(Transform root)
    {
        Material body = Mat("Locker", new Color32(52, 66, 78, 255), null, 0f, 0.7f, 0.5f);
        Material darkM = Mat("VaultDark", new Color32(20, 26, 32, 255), null, 0f, 0.8f, 0.45f);
        Material handle = Mat("VaultBolt", new Color32(140, 152, 164, 255), null, 0f, 1f, 0.7f);
        Material orange = Glow("NeonOrange", Orange, 3f);

        for (int i = 0; i < 5; i++)
        {
            float z = 0.6f + i * 0.72f;
            GameObject l = new GameObject("Locker" + i);
            l.transform.SetParent(root, false);
            l.transform.localPosition = new Vector3(5.5f, 0f, z);
            Transform t = l.transform;
            Prim(PrimitiveType.Cube, "Body", t, new Vector3(0f, 1.05f, 0f), new Vector3(0.6f, 2.1f, 0.7f), body, true);
            Prim(PrimitiveType.Cube, "DoorLine", t, new Vector3(-0.31f, 1.05f, 0f), new Vector3(0.01f, 2.0f, 0.62f), darkM, false);
            for (int v = 0; v < 4; v++)
                Prim(PrimitiveType.Cube, "Vent" + v, t, new Vector3(-0.32f, 1.75f - v * 0.06f, 0f), new Vector3(0.01f, 0.02f, 0.4f), darkM, false);
            Prim(PrimitiveType.Cube, "Handle", t, new Vector3(-0.33f, 1.05f, 0.25f), new Vector3(0.03f, 0.2f, 0.03f), handle, false);
            Prim(PrimitiveType.Cube, "NumberPlate", t, new Vector3(-0.32f, 1.9f, 0f), new Vector3(0.01f, 0.1f, 0.2f), orange, false);
            WText("Num", t, "0" + (i + 1), 0.8f, new Vector3(-0.33f, 1.9f, 0f), new Vector3(0f, 90f, 0f), new Color32(25, 12, 0, 255));
        }
    }

    static void ClueBoard(Transform root)
    {
        Material board = Mat("Board", new Color32(40, 32, 28, 255), null, 0f, 0.1f, 0.2f);
        Material paper = Mat("Paper", new Color32(225, 222, 205, 255), null, 0f, 0f, 0.2f);
        Material redM = Glow("VaultRed", Red, 2.5f);

        Vector3 p = new Vector3(5.82f, 1.8f, -2.2f);
        Prim(PrimitiveType.Cube, "ClueBoard", root, p, new Vector3(0.05f, 1.3f, 2.0f), board, false);
        float[] zs = { -0.7f, -0.2f, 0.4f, 0.75f, -0.4f };
        float[] ys = { 0.25f, -0.25f, 0.3f, -0.2f, 0.4f };
        for (int i = 0; i < 5; i++)
        {
            GameObject n = Prim(PrimitiveType.Cube, "Note" + i, root, p + new Vector3(-0.04f, ys[i], zs[i]), new Vector3(0.01f, 0.28f, 0.25f), paper, false);
            n.transform.localEulerAngles = new Vector3(Random.Range(-10f, 10f), 0f, 0f);
            Prim(PrimitiveType.Sphere, "Pin" + i, root, p + new Vector3(-0.05f, ys[i] + 0.1f, zs[i]), new Vector3(0.04f, 0.04f, 0.04f), redM, false);
        }
        WText("ClueTitle", root, "CASE FILE: BUG-13", 1.2f, new Vector3(5.78f, 2.62f, -2.2f), new Vector3(0f, 90f, 0f), Orange);
        WText("ClueQ", root, "?  ?  ?", 2f, new Vector3(5.78f, 1.8f, -2.2f), new Vector3(0f, 90f, 0f), new Color32(255, 80, 80, 255));
    }

    static void Crates(Transform root)
    {
        Material crate = Mat("Crate", new Color32(58, 50, 40, 255), null, 0f, 0.1f, 0.35f);
        Material orange = Glow("NeonOrange", Orange, 2.5f);

        Prim(PrimitiveType.Cube, "Crate1", root, new Vector3(5.0f, 0.4f, -3.9f), new Vector3(0.8f, 0.8f, 0.8f), crate, true);
        GameObject c2 = Prim(PrimitiveType.Cube, "Crate2", root, new Vector3(5.0f, 1.2f, -3.9f), new Vector3(0.7f, 0.7f, 0.7f), crate, true);
        c2.transform.localEulerAngles = new Vector3(0f, 18f, 0f);
        Prim(PrimitiveType.Cube, "Crate3", root, new Vector3(4.0f, 0.35f, -4.2f), new Vector3(0.7f, 0.7f, 0.7f), crate, true);
        Prim(PrimitiveType.Cube, "Crate4", root, new Vector3(5.1f, 0.3f, -2.9f), new Vector3(0.6f, 0.6f, 0.6f), crate, true);
        Prim(PrimitiveType.Cube, "CrateStripe1", root, new Vector3(5.0f, 0.62f, -3.49f), new Vector3(0.82f, 0.06f, 0.02f), orange, false);
        WText("CrateLabel", root, "!! BUGS !!", 1.0f, new Vector3(5.0f, 0.36f, -3.47f), new Vector3(0f, 180f, 0f), Orange);
    }

    static void WetFloorSign(Transform root)
    {
        Material yellow = Mat("Caution", new Color32(240, 200, 30, 255), null, 0f, 0.1f, 0.5f);
        GameObject s = new GameObject("WetFloorSign");
        s.transform.SetParent(root, false);
        s.transform.localPosition = new Vector3(-2.4f, 0f, 1.8f);
        s.transform.localEulerAngles = new Vector3(0f, 25f, 0f);
        GameObject a = Prim(PrimitiveType.Cube, "SideA", s.transform, new Vector3(0f, 0.3f, -0.12f), new Vector3(0.35f, 0.6f, 0.02f), yellow, false);
        a.transform.localEulerAngles = new Vector3(-12f, 0f, 0f);
        GameObject b = Prim(PrimitiveType.Cube, "SideB", s.transform, new Vector3(0f, 0.3f, 0.12f), new Vector3(0.35f, 0.6f, 0.02f), yellow, false);
        b.transform.localEulerAngles = new Vector3(12f, 0f, 0f);
        WText("Caution", s.transform, "CAUTION", 0.6f, new Vector3(0f, 0.3f, -0.14f), new Vector3(0f, 180f, 0f), new Color32(20, 20, 20, 255));
    }

    static void Alarms(Transform root)
    {
        Material redM = Glow("VaultRed", Red, 4f);
        Vector3[] spots = { new Vector3(-5.75f, 3.0f, 3.8f), new Vector3(5.75f, 3.0f, 3.8f), new Vector3(-5.75f, 3.0f, -3.8f), new Vector3(5.75f, 3.0f, -3.8f) };
        for (int i = 0; i < spots.Length; i++)
        {
            GameObject a = Prim(PrimitiveType.Sphere, "AlarmLamp" + i, root, spots[i], new Vector3(0.22f, 0.22f, 0.22f), redM, false);
            PulseGlow pg = a.AddComponent<PulseGlow>();
            pg.speed = 3f; pg.minFactor = 0.1f; pg.emission = Red; pg.intensity = 4f;
            GameObject l = new GameObject("AlarmLight" + i);
            l.transform.SetParent(a.transform, false);
            Light li = l.AddComponent<Light>();
            li.type = LightType.Point; li.color = Red; li.intensity = 4f; li.range = 6f;
            PulseGlow lp = l.AddComponent<PulseGlow>();
            lp.speed = 3f; lp.minFactor = 0.05f;
        }
    }

    static void Pipes(Transform root)
    {
        Material pipe = Mat("Pipe", new Color32(40, 52, 64, 255), null, 0f, 0.9f, 0.6f);
        Material ring = Glow("NeonTeal", Teal, 3f);
        float[] zs = { -4.5f, -1.5f, 4.5f };
        foreach (float z in zs)
        {
            GameObject p = Prim(PrimitiveType.Cylinder, "Pipe_" + z, root, new Vector3(0f, 3.25f, z), new Vector3(0.2f, 5.9f, 0.2f), pipe, false);
            p.transform.localEulerAngles = new Vector3(0f, 0f, 90f);
            for (int i = -2; i <= 2; i++)
            {
                GameObject band = Prim(PrimitiveType.Cylinder, "PipeBand", root, new Vector3(i * 2.2f, 3.25f, z), new Vector3(0.24f, 0.03f, 0.24f), ring, false);
                band.transform.localEulerAngles = new Vector3(0f, 0f, 90f);
            }
        }
        // cable trays along the side walls
        Material tray = Mat("Tray", new Color32(18, 24, 30, 255), null, 0f, 0.7f, 0.4f);
        Prim(PrimitiveType.Cube, "TrayL", root, new Vector3(-5.7f, 3.2f, 0f), new Vector3(0.3f, 0.06f, 9.6f), tray, false);
        Prim(PrimitiveType.Cube, "TrayR", root, new Vector3(5.7f, 3.2f, 0f), new Vector3(0.3f, 0.06f, 9.6f), tray, false);
    }

    static void CeilingLights(Transform root)
    {
        Material tube = Glow("TubeLight", new Color32(210, 235, 245, 255), 3f);
        float[] zs = { -3.2f, 0.2f, 3.0f };
        foreach (float z in zs)
        {
            Prim(PrimitiveType.Cube, "Tube_" + z, root, new Vector3(0f, 3.46f, z), new Vector3(3f, 0.05f, 0.18f), tube, false);
            GameObject lamp = new GameObject("TubeLamp_" + z);
            lamp.transform.SetParent(root, false);
            lamp.transform.localPosition = new Vector3(0f, 3.1f, z);
            Light l = lamp.AddComponent<Light>();
            l.type = LightType.Point; l.color = new Color(0.7f, 0.9f, 1f); l.intensity = 1.6f; l.range = 7f;
        }

        GameObject faulty = GameObject.Find("Tube_" + 3f);
        if (faulty != null)
        {
            PulseGlow pg = faulty.AddComponent<PulseGlow>();
            pg.flicker = true; pg.speed = 3f; pg.minFactor = 0.05f; pg.emission = new Color(0.82f, 0.92f, 0.96f); pg.intensity = 3f;
        }
    }

    static void Signs(Transform root)
    {
        WText("SignMonitor", root, "MISSION BRIEFING", 1.3f, new Vector3(MonitorPos.x, 3.0f, 4.78f), Vector3.zero, Orange);
        WText("SignLocked", root, "SYSTEM LOCKED", 1.1f, new Vector3(KeypadPos.x, 2.3f, 4.78f), Vector3.zero, Red);

        // behind the player
        WText("SignNoBug", root, "NO BUG LEFT BEHIND", 4f, new Vector3(0f, 2.6f, -4.8f), new Vector3(0f, 180f, 0f), Teal);
        WText("SignTest", root, "TEST EVERYTHING", 2f, new Vector3(0f, 2.1f, -4.8f), new Vector3(0f, 180f, 0f), Orange);

        // left wall
        WText("SignTracker", root, "BUG TRACKER\n[13 OPEN]  [0 FIXED]", 1.6f, new Vector3(-5.8f, 2.7f, 1.4f), new Vector3(0f, -90f, 0f), Green);
        WText("SignRacks", root, "SERVER ROOM B", 1.5f, new Vector3(-5.8f, 2.9f, -2.0f), new Vector3(0f, -90f, 0f), Teal);
        // right wall
        WText("SignLockers", root, "EVIDENCE LOCKERS", 1.5f, new Vector3(5.8f, 2.7f, 2.2f), new Vector3(0f, 90f, 0f), Orange);
    }

    // ---------- A few flying bugs ----------

    static void BugSwarm(Transform root)
    {
        GameObject swarm = new GameObject("BugSwarm");
        swarm.transform.SetParent(root, false);
        Color[] colors = { Green, Orange, Red, Teal };

        for (int i = 0; i < 8; i++)
        {
            Vector3 p = new Vector3(Random.Range(-4.5f, 4.5f), Random.Range(1.4f, 3.0f), Random.Range(-3.0f, 3.5f));
            BuildBug(swarm.transform, "Bug" + i, p, colors[i % colors.Length], Random.Range(0.45f, 0.8f));
        }
    }

    static void BuildBug(Transform parent, string name, Vector3 pos, Color col, float scale)
    {
        GameObject bug = new GameObject(name);
        bug.transform.SetParent(parent, false);
        bug.transform.localPosition = pos;
        bug.transform.localScale = Vector3.one * scale;
        Transform b = bug.transform;

        Material body = Mat("BugBody_" + ColorKey(col), new Color(col.r * 0.45f, col.g * 0.45f, col.b * 0.45f, 1f), col, 0.7f, 0.2f, 0.6f);
        Material eye = Glow("BugEye", new Color32(255, 255, 255, 255), 4f);
        Material wing = Glow("BugWing", new Color32(150, 230, 255, 255), 1.5f);

        Prim(PrimitiveType.Sphere, "Body", b, Vector3.zero, new Vector3(0.5f, 0.4f, 0.8f), body, false);
        Prim(PrimitiveType.Sphere, "Head", b, new Vector3(0f, 0.03f, 0.5f), new Vector3(0.34f, 0.3f, 0.3f), body, false);
        Prim(PrimitiveType.Sphere, "EyeL", b, new Vector3(-0.1f, 0.1f, 0.64f), new Vector3(0.1f, 0.1f, 0.1f), eye, false);
        Prim(PrimitiveType.Sphere, "EyeR", b, new Vector3(0.1f, 0.1f, 0.64f), new Vector3(0.1f, 0.1f, 0.1f), eye, false);

        GameObject aL = Prim(PrimitiveType.Cylinder, "AntennaL", b, new Vector3(-0.08f, 0.24f, 0.72f), new Vector3(0.025f, 0.18f, 0.025f), body, false);
        aL.transform.localEulerAngles = new Vector3(50f, -20f, 0f);
        GameObject aR = Prim(PrimitiveType.Cylinder, "AntennaR", b, new Vector3(0.08f, 0.24f, 0.72f), new Vector3(0.025f, 0.18f, 0.025f), body, false);
        aR.transform.localEulerAngles = new Vector3(50f, 20f, 0f);

        float[] legZ = { -0.2f, 0.05f, 0.3f };
        foreach (float z in legZ)
        {
            GameObject lL = Prim(PrimitiveType.Cylinder, "LegL", b, new Vector3(-0.27f, -0.12f, z), new Vector3(0.025f, 0.16f, 0.025f), body, false);
            lL.transform.localEulerAngles = new Vector3(0f, 0f, -55f);
            GameObject lR = Prim(PrimitiveType.Cylinder, "LegR", b, new Vector3(0.27f, -0.12f, z), new Vector3(0.025f, 0.16f, 0.025f), body, false);
            lR.transform.localEulerAngles = new Vector3(0f, 0f, 55f);
        }

        // Wings hinge on a container so they flap around the body
        GameObject wl = new GameObject("WingL");
        wl.transform.SetParent(b, false);
        wl.transform.localPosition = new Vector3(-0.12f, 0.22f, 0.05f);
        Prim(PrimitiveType.Cube, "Wing", wl.transform, new Vector3(-0.3f, 0f, 0f), new Vector3(0.55f, 0.015f, 0.32f), wing, false);

        GameObject wr = new GameObject("WingR");
        wr.transform.SetParent(b, false);
        wr.transform.localPosition = new Vector3(0.12f, 0.22f, 0.05f);
        Prim(PrimitiveType.Cube, "Wing", wr.transform, new Vector3(0.3f, 0f, 0f), new Vector3(0.55f, 0.015f, 0.32f), wing, false);

        bug.AddComponent<FlyingBug>();
    }


    // ---------- Atmosphere ----------

    static void Atmosphere(Transform root)
    {
        RenderSettings.ambientMode = UnityEngine.Rendering.AmbientMode.Flat;
        RenderSettings.ambientLight = new Color(0.09f, 0.11f, 0.13f);
        RenderSettings.fog = true;
        RenderSettings.fogMode = FogMode.ExponentialSquared;
        RenderSettings.fogColor = new Color(0.03f, 0.05f, 0.07f);
        RenderSettings.fogDensity = 0.012f;

        foreach (Light l in Object.FindObjectsByType<Light>(FindObjectsSortMode.None))
        {
            if (l.type == LightType.Directional)
            {
                Undo.RecordObject(l, "Dim light");
                l.intensity = 0.2f;
                l.color = new Color(0.6f, 0.75f, 1f);
            }
        }

        // reflections for the shiny floor and metal
        GameObject probeGo = new GameObject("ReflectionProbe");
        probeGo.transform.SetParent(root, false);
        probeGo.transform.localPosition = new Vector3(0f, 1.8f, 0f);
        ReflectionProbe probe = probeGo.AddComponent<ReflectionProbe>();
        probe.mode = UnityEngine.Rendering.ReflectionProbeMode.Realtime;
        probe.refreshMode = UnityEngine.Rendering.ReflectionProbeRefreshMode.OnAwake;
        probe.size = new Vector3(12f, 3.6f, 10f);
        probe.boxProjection = true;
        probe.resolution = 128;
    }

    // Bloom makes the neon glow. Wrapped in try/catch: if it fails the room still works.
    static void PostProcessing()
    {
        try
        {
            string path = Folder + "/HomeVolume.asset";
            AssetDatabase.DeleteAsset(path);

            var profile = ScriptableObject.CreateInstance<UnityEngine.Rendering.VolumeProfile>();
            AssetDatabase.CreateAsset(profile, path);

            var bloom = profile.Add<UnityEngine.Rendering.Universal.Bloom>(true);
            bloom.threshold.Override(0.8f);
            bloom.intensity.Override(1.3f);
            bloom.scatter.Override(0.65f);

            var vignette = profile.Add<UnityEngine.Rendering.Universal.Vignette>(true);
            vignette.intensity.Override(0.32f);

            foreach (var component in profile.components)
            {
                component.name = component.GetType().Name;
                AssetDatabase.AddObjectToAsset(component, profile);
            }
            EditorUtility.SetDirty(profile);

            GameObject volGo = new GameObject("HomeVolume");
            Undo.RegisterCreatedObjectUndo(volGo, "Build Home Environment");
            var volume = volGo.AddComponent<UnityEngine.Rendering.Volume>();
            volume.isGlobal = true;
            volume.priority = 10f;
            volume.sharedProfile = profile;
        }
        catch (System.Exception e)
        {
            Debug.LogWarning("Glow (post-processing) could not be added: " + e.Message);
        }
    }

    // ---------- Helpers ----------

    static void EnsureFolders()
    {
        if (!AssetDatabase.IsValidFolder(Folder)) AssetDatabase.CreateFolder("Assets", "HomeTheme");
        if (!AssetDatabase.IsValidFolder(MatFolder)) AssetDatabase.CreateFolder(Folder, "Materials");
    }

    static void DestroyIfExists(string name)
    {
        GameObject old = GameObject.Find(name);
        while (old != null)
        {
            Undo.DestroyObjectImmediate(old);
            old = GameObject.Find(name);
        }
    }

    static string ColorKey(Color c)
    {
        return Mathf.RoundToInt(c.r * 255f) + "_" + Mathf.RoundToInt(c.g * 255f) + "_" + Mathf.RoundToInt(c.b * 255f);
    }

    static Material Glow(string name, Color color, float intensity)
    {
        return Mat(name, new Color(color.r * 0.2f, color.g * 0.2f, color.b * 0.2f, 1f), color, intensity, 0f, 0.3f);
    }

    static Material Mat(string name, Color baseColor, Color? emission, float emissionIntensity, float metallic, float smoothness)
    {
        Material m;
        if (cache.TryGetValue(name, out m) && m != null) return m;

        string path = MatFolder + "/" + name + ".mat";
        Shader shader = Shader.Find("Universal Render Pipeline/Lit");
        if (shader == null) shader = Shader.Find("Standard");

        m = AssetDatabase.LoadAssetAtPath<Material>(path);
        if (m == null)
        {
            m = new Material(shader);
            AssetDatabase.CreateAsset(m, path);
        }
        else
        {
            m.shader = shader;
        }

        m.SetColor("_BaseColor", baseColor);
        m.SetColor("_Color", baseColor);
        m.SetFloat("_Metallic", metallic);
        m.SetFloat("_Smoothness", smoothness);

        if (emission.HasValue)
        {
            m.EnableKeyword("_EMISSION");
            m.SetColor("_EmissionColor", emission.Value * emissionIntensity);
            m.globalIlluminationFlags = MaterialGlobalIlluminationFlags.None;
        }
        else
        {
            m.DisableKeyword("_EMISSION");
            m.SetColor("_EmissionColor", Color.black);
        }

        EditorUtility.SetDirty(m);
        cache[name] = m;
        return m;
    }

    static GameObject Prim(PrimitiveType type, string name, Transform parent, Vector3 localPos, Vector3 scale, Material mat, bool keepCollider)
    {
        GameObject go = GameObject.CreatePrimitive(type);
        go.name = name;
        go.transform.SetParent(parent, false);
        go.transform.localPosition = localPos;
        go.transform.localScale = scale;
        if (mat != null) go.GetComponent<Renderer>().sharedMaterial = mat;
        if (!keepCollider)
        {
            Collider c = go.GetComponent<Collider>();
            if (c != null) Object.DestroyImmediate(c);
        }
        return go;
    }

    static TextMeshPro WText(string name, Transform parent, string text, float size, Vector3 localPos, Vector3 euler, Color color)
    {
        return WText(name, parent, text, size, localPos, euler, color, TextAlignmentOptions.Center);
    }

    static TextMeshPro WText(string name, Transform parent, string text, float size, Vector3 localPos, Vector3 euler, Color color, TextAlignmentOptions align)
    {
        GameObject go = new GameObject(name);
        go.transform.SetParent(parent, false);
        go.transform.localPosition = localPos;
        go.transform.localEulerAngles = euler;
        TextMeshPro t = go.AddComponent<TextMeshPro>();
        t.text = text;
        t.fontSize = size;
        t.color = color;
        t.alignment = align;
        t.rectTransform.sizeDelta = new Vector2(12f, 3f);
        return t;
    }
}
#endif
