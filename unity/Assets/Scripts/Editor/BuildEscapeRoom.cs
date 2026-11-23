#if UNITY_EDITOR
using System.Collections.Generic;
using System.IO;
using TMPro;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.Rendering;
using UnityEngine.SceneManagement;
using UnityEngine.UI;
using UnityEngine.XR.Interaction.Toolkit.UI;

// Menu: The Last Protocol > L1 - Build EscapeRoom_Main   (the laboratory with the three keypad pedestals)
//       The Last Protocol > L2 - Create Room Placeholder Scenes
// L1 copies your finished MainMenu hall (vault, beacon, racks, floor) into EscapeRoom_Main, removes the menu,
// and adds: 3 pedestals with keypads, cables, workstations, cabinets, the access system and the vault controller.
public static partial class BuildEscapeRoom
{
    const string MenuScene = "Assets/Scenes 1/MainMenu.unity";
    const string LabScene = "Assets/Scenes 1/EscapeRoom_Main.unity";
    const string Mats = "Assets/MainMenu/Materials";
    const string Gen = "Assets/MainMenu/Generated";

    static readonly string[] RoomNames = { "ROOM ALPHA", "ROOM BETA", "ROOM GAMMA" };
    static readonly string[] RoomScenes = { "Room_Alpha", "Room_Beta", "Room_Gamma" };
    static readonly string[] PlaceholderCodes = { "111111", "222222", "333333" };      // CHANGE THESE in the Inspector

    static readonly Color Amber = new Color(1f, 0.55f, 0.12f);
    static readonly Vector3 RigStart = new Vector3(0f, 0f, -5.0f);
    static readonly Vector3 SemiCenter = new Vector3(0f, 0f, -2.0f);
    const float SemiRadius = 3.0f;

    static Material steel, darkSteel, black, amberGlow, lampGreen, lampRed, screenBlue, screenGreen, rubber, white;
    static TMP_FontAsset fontBold, fontMed;
    static AudioClip clKey, clClear, clGranted, clDenied, clLock, clDoor;

    // =====================================================================================================
    [MenuItem("The Last Protocol/L1 - Build EscapeRoom_Main")]
    static void Build()
    {
        if (!File.Exists(MenuScene)) { EditorUtility.DisplayDialog("MainMenu missing", "Assets/Scenes 1/MainMenu.unity was not found.", "OK"); return; }
        if (!EditorSceneManager.SaveCurrentModifiedScenesIfUserWantsTo()) return;

        // 1. copy the finished hall into EscapeRoom_Main (this replaces the empty placeholder made earlier)
        EditorSceneManager.OpenScene(MenuScene, OpenSceneMode.Single);
        EditorSceneManager.SaveScene(SceneManager.GetActiveScene(), LabScene, true);
        EditorSceneManager.OpenScene(LabScene, OpenSceneMode.Single);

        LoadAssets();
        StripMenu();
        RestoreCodedVault();

        // 2. the player
        GameObject rig = GameObject.Find("XR Origin Hands (XR Rig)");
        if (rig == null) { Debug.LogError("XR Origin not found in the copied scene. Run the menu steps 1-5 first."); return; }
        rig.transform.position = RigStart;
        rig.transform.rotation = Quaternion.identity;                       // faces +Z: the pedestals and the vault
        Transform loco = Deep(rig.transform, "Locomotion");
        if (loco != null) loco.gameObject.SetActive(true);
        Camera cam = rig.GetComponentInChildren<Camera>(true);

        // 3. systems
        GameObject sys = new GameObject("EscapeRoomSystem");
        AccessCodeManager codes = sys.AddComponent<AccessCodeManager>();
        for (int i = 0; i < 3; i++)
            codes.rooms.Add(new AccessRoom { roomName = RoomNames[i], code = PlaceholderCodes[i], destinationScene = RoomScenes[i], status = AccessStatus.Locked, useWebsiteCode = (i == 0) });

        SceneTransitionManager stm = sys.AddComponent<SceneTransitionManager>();
        Transform fq = cam != null ? cam.transform.Find("VRFade") : null;
        if (fq != null) stm.fadeQuad = fq.GetComponent<Renderer>(); else Debug.LogWarning("VRFade quad not found; scene changes will not fade.");

        PlayerInteraction pi = rig.GetComponent<PlayerInteraction>();
        if (pi == null) pi = rig.AddComponent<PlayerInteraction>();
        pi.playerCamera = cam; pi.locomotion = loco != null ? loco.gameObject : null;

        VaultDoorController vault = SetupVault();

        // 4. the reference room: dais with three pedestals, cables, kiosk, desks, counter, wall monitor, papers ...
        Transform root = new GameObject("LabProps").transform;
        List<Transform> peds = new List<Transform>();
        Vector3[] pedPos = { new Vector3(-1.6f, 0.22f, 0.9f), new Vector3(0f, 0.22f, -0.7f), new Vector3(1.6f, 0.22f, 0.9f) };
        BuildDais(root);
        for (int i = 0; i < 3; i++)
        {
            Vector3 toPlayer = RigStart - pedPos[i]; toPlayer.y = 0f; toPlayer.Normalize();
            peds.Add(BuildPedestal(i, root, pedPos[i], Quaternion.LookRotation(-toPlayer), cam, vault));   // front (-Z) faces the player
        }
        BuildReferenceRoom(root, peds);
        if (rig.GetComponent<PlayerHeightFix>() == null) rig.AddComponent<PlayerHeightFix>();

        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        EditorSceneManager.SaveScene(SceneManager.GetActiveScene());
        AddToBuildSettings(MenuScene, LabScene);
        Debug.Log("EscapeRoom_Main built and saved. Codes are placeholders 111111 / 222222 / 333333: change them on the 'EscapeRoomSystem' object. Next: run 'L2 - Create Room Placeholder Scenes'.");
    }

    // =====================================================================================================
    static void LoadAssets()
    {
        steel = LoadMat("M_Steel"); darkSteel = LoadMat("M_SteelDark"); black = LoadMat("M_Black"); amberGlow = LoadMat("M_AmberGlow");
        white = Flat("M_LabWhite", new Color(0.8f, 0.82f, 0.85f), 0.2f, 0.5f);
        rubber = Flat("M_LabRubber", new Color(0.03f, 0.03f, 0.035f), 0f, 0.35f);
        lampGreen = Emissive("M_LampGreen", new Color(0.02f, 0.15f, 0.05f), new Color(0.2f, 1f, 0.45f) * 4f);
        lampRed = Emissive("M_LampRed", new Color(0.15f, 0.02f, 0.02f), new Color(1f, 0.1f, 0.08f) * 4f);
        screenBlue = Emissive("M_ScreenBlue", new Color(0.01f, 0.04f, 0.08f), new Color(0.15f, 0.55f, 1f) * 1.6f);
        screenGreen = Emissive("M_ScreenGreen", new Color(0.01f, 0.06f, 0.03f), new Color(0.2f, 1f, 0.5f) * 1.4f);
        fontBold = AssetDatabase.LoadAssetAtPath<TMP_FontAsset>(Gen + "/Orbitron Bold SDF.asset");
        fontMed = AssetDatabase.LoadAssetAtPath<TMP_FontAsset>(Gen + "/Orbitron Medium SDF.asset");
        clKey = Clip("sfx_keypadClick");
        clClear = Clip("sfx_keypadClear") ?? clKey;
        clGranted = Clip("sfx_keypadGranted");
        clDenied = Clip("sfx_keypadDenied") ?? Clip("sfx_keypadWrong") ?? Clip("sfx_keypadError") ?? MakeDeniedClip();
        clLock = Clip("734641__vrymaa__lock-strong-door");
        clDoor = Clip("426624__mrh4hn__heavy-metal-door-far-away");
    }

    // remove everything that belonged to the menu
    static void StripMenu()
    {
        foreach (string n in new[] { "MenuCanvas", "MenuPlacement", "GlbVault", "FadeOverlay" })
            foreach (Transform t in Resources.FindObjectsOfTypeAll<Transform>())
                if (t != null && t.name == n && t.gameObject.scene.IsValid()) Object.DestroyImmediate(t.gameObject);
        foreach (MenuManager m in Object.FindObjectsByType<MenuManager>(FindObjectsInactive.Include, FindObjectsSortMode.None)) Object.DestroyImmediate(m);
        foreach (VaultSequence v in Object.FindObjectsByType<VaultSequence>(FindObjectsInactive.Include, FindObjectsSortMode.None)) Object.DestroyImmediate(v);
        foreach (SettingsPanel s in Object.FindObjectsByType<SettingsPanel>(FindObjectsInactive.Include, FindObjectsSortMode.None)) Object.DestroyImmediate(s);
        foreach (ReturnToMenu r in Object.FindObjectsByType<ReturnToMenu>(FindObjectsInactive.Include, FindObjectsSortMode.None)) Object.DestroyImmediate(r);
    }

    // if the downloaded vault experiment hid parts of the coded vault in the menu scene, bring them back
    static void RestoreCodedVault()
    {
        Transform door = FindAny("DoorAssembly");
        if (door == null) return;
        door.gameObject.SetActive(true);
        if (door.parent != null)
            foreach (Renderer r in door.parent.GetComponentsInChildren<Renderer>(true)) r.enabled = true;
    }

    static VaultDoorController SetupVault()
    {
        GameObject v = GameObject.Find("VaultDoor");
        if (v == null) { Debug.LogError("VaultDoor not found."); return null; }
        VaultDoorController c = v.GetComponent<VaultDoorController>();
        if (c == null) c = v.AddComponent<VaultDoorController>();
        c.doorAssembly = Deep(v.transform, "DoorAssembly");
        c.wheel = Deep(v.transform, "Wheel");
        c.lockBolts = Deep(v.transform, "LockBolts");
        Transform lamp = Deep(v.transform, "StatusLamp"); c.lamp = lamp != null ? lamp.GetComponent<Renderer>() : null;
        Transform st = Deep(v.transform, "StatusText"); c.statusText = st != null ? st.GetComponent<TMP_Text>() : null;
        Transform bl = Deep(v.transform, "BehindLight"); c.lightBehind = bl != null ? bl.gameObject : null;
        Transform bg = Deep(v.transform, "BehindGlow"); c.glowBehind = bg != null ? bg.gameObject : null;
        c.glow = v.GetComponent<VaultGlow>();
        c.beacon = Object.FindFirstObjectByType<MenuBeacon>();
        AudioSource src = v.GetComponent<AudioSource>();
        if (src == null) src = v.AddComponent<AudioSource>();
        src.playOnAwake = false; src.spatialBlend = 0.6f; src.maxDistance = 30f;
        c.audioSource = src; c.lockClip = clLock; c.doorClip = clDoor;
        EditorUtility.SetDirty(c);
        return c;
    }

    // =====================================================================================================
    //  PEDESTAL
    // =====================================================================================================
    static Transform BuildPedestal(int index, Transform parent, Vector3 pos, Quaternion rot, Camera cam, VaultDoorController vault)
    {
        GameObject p = new GameObject("Pedestal_0" + (index + 1) + "_" + RoomNames[index].Replace(' ', '_'));
        p.transform.SetParent(parent, false);
        p.transform.SetPositionAndRotation(pos, rot);

        // boxy gunmetal column (0.9 x 0.9 x 0.8 m) with glowing amber edges and a blue glowing top, like the reference
        GameObject body = Prim(PrimitiveType.Cube, "Body", p.transform, new Vector3(0, 0.45f, 0), new Vector3(0.9f, 0.9f, 0.8f), darkSteel);   // collider: the E-ray hits this
        Prim(PrimitiveType.Cube, "Base", p.transform, new Vector3(0, 0.03f, 0), new Vector3(0.96f, 0.06f, 0.86f), black);
        Prim(PrimitiveType.Cube, "Cap", p.transform, new Vector3(0, 0.915f, 0), new Vector3(0.96f, 0.03f, 0.86f), steel);
        Prim(PrimitiveType.Cube, "TopGlow", p.transform, new Vector3(0, 0.935f, 0), new Vector3(0.7f, 0.012f, 0.6f), screenBlue);
        foreach (int sx in new[] { -1, 1 })
        {
            Prim(PrimitiveType.Cube, "EdgeFront", p.transform, new Vector3(sx * 0.455f, 0.45f, -0.405f), new Vector3(0.03f, 0.78f, 0.03f), amberGlow);
            Prim(PrimitiveType.Cube, "EdgeBack", p.transform, new Vector3(sx * 0.455f, 0.45f, 0.405f), new Vector3(0.03f, 0.78f, 0.03f), amberGlow);
            Prim(PrimitiveType.Cube, "Rivet", p.transform, new Vector3(sx * 0.46f, 0.86f, -0.2f), new Vector3(0.02f, 0.03f, 0.03f), black);
        }
        Prim(PrimitiveType.Cube, "BaseGlow", p.transform, new Vector3(0, 0.075f, -0.405f), new Vector3(0.7f, 0.014f, 0.014f), amberGlow);

        // keypad canvas on the front face
        Transform pivot = new GameObject("FacePivot").transform;
        pivot.SetParent(p.transform, false);
        pivot.localPosition = new Vector3(0, 0.47f, -0.36f);
        KeypadController kp = BuildKeypadCanvas(pivot, cam, index);

        // lamps on the top plate, front edge
        GameObject g = Prim(PrimitiveType.Sphere, "GreenLamp", p.transform, new Vector3(-0.3f, 0.95f, -0.3f), Vector3.one * 0.07f, lampGreen);
        GameObject r = Prim(PrimitiveType.Sphere, "RedLamp", p.transform, new Vector3(0.3f, 0.95f, -0.3f), Vector3.one * 0.07f, lampRed);
        Object.DestroyImmediate(g.GetComponent<Collider>()); Object.DestroyImmediate(r.GetComponent<Collider>());
        Light gl = NewLight("GreenLight", g.transform, new Color(0.3f, 1f, 0.5f), 2.5f, 1.8f);
        Light rl = NewLight("RedLight", r.transform, new Color(1f, 0.15f, 0.1f), 2.5f, 1.8f);
        kp.greenLamp = g.GetComponent<Renderer>(); kp.redLamp = r.GetComponent<Renderer>();
        kp.greenLight = gl; kp.redLight = rl;
        NewLight("TopBlueLight", p.transform, new Color(0.3f, 0.6f, 1f), 2.2f, 2.5f).transform.localPosition = new Vector3(0, 1.1f, 0);

        AudioSource src = p.AddComponent<AudioSource>();
        src.playOnAwake = false; src.spatialBlend = 1f; src.maxDistance = 12f; src.rolloffMode = AudioRolloffMode.Linear;
        kp.audioSource = src; kp.keyClip = clKey; kp.clearClip = clClear; kp.grantedClip = clGranted; kp.deniedClip = clDenied;

        // floating room name above the pedestal (always readable)
        TextMeshPro sign = Text3D("RoomSign", p.transform, RoomNames[index], 2.6f, new Vector3(0, 1.42f, 0f), Vector3.zero, new Color(1f, 0.62f, 0.2f), new Vector2(1.6f, 0.3f));
        if (fontBold != null) sign.font = fontBold;
        Prim(PrimitiveType.Cube, "SignLine", p.transform, new Vector3(0, 1.25f, 0f), new Vector3(1.0f, 0.012f, 0.012f), amberGlow);

        // spotlight from the ceiling
        GameObject sp = new GameObject("Spot");
        sp.transform.SetParent(p.transform, false);
        sp.transform.position = pos + new Vector3(0, 4.9f, -1.0f);
        sp.transform.rotation = Quaternion.LookRotation(Vector3.down);
        Light spl = sp.AddComponent<Light>();
        spl.type = LightType.Spot; spl.spotAngle = 50f; spl.range = 8f; spl.intensity = 35f; spl.color = new Color(1f, 0.85f, 0.65f); spl.shadows = LightShadows.None;

        AccessPedestal ap = p.AddComponent<AccessPedestal>();
        ap.roomIndex = index; ap.keypad = kp; ap.vault = vault;
        return p.transform;
    }

    static KeypadController BuildKeypadCanvas(Transform pivot, Camera cam, int index)
    {
        GameObject cgo = new GameObject("KeypadCanvas", typeof(RectTransform));
        cgo.transform.SetParent(pivot, false);
        Canvas canvas = cgo.AddComponent<Canvas>();
        canvas.renderMode = RenderMode.WorldSpace; canvas.worldCamera = cam;
        CanvasScaler cs = cgo.AddComponent<CanvasScaler>(); cs.uiScaleMode = CanvasScaler.ScaleMode.ConstantPixelSize; cs.dynamicPixelsPerUnit = 4f;
        cgo.AddComponent<GraphicRaycaster>();
        cgo.AddComponent<TrackedDeviceGraphicRaycaster>();
        RectTransform rt = (RectTransform)cgo.transform;
        rt.sizeDelta = new Vector2(580, 680);
        rt.localScale = Vector3.one * 0.001f;
        rt.localPosition = new Vector3(0, 0, -0.0425f);
        rt.localRotation = Quaternion.identity;

        KeypadController kp = cgo.AddComponent<KeypadController>();

        Image bg = UIRect("Background", rt, Vector2.zero, new Vector2(580, 680)).gameObject.AddComponent<Image>();
        bg.color = new Color(0.025f, 0.03f, 0.04f, 1f); bg.raycastTarget = false;
        Outline ol = bg.gameObject.AddComponent<Outline>(); ol.effectColor = new Color(1f, 0.55f, 0.12f, 0.7f); ol.effectDistance = new Vector2(3, -3);

        kp.roomLabel = UIText("RoomName", rt, RoomNames[index], 40, new Vector2(-20, 292), new Vector2(480, 60), new Color(1f, 0.65f, 0.25f), fontBold);
        Image dbg = UIRect("DisplayPanel", rt, new Vector2(0, 200), new Vector2(500, 100)).gameObject.AddComponent<Image>();
        dbg.color = new Color(0, 0.02f, 0.03f, 1f); dbg.raycastTarget = false;
        Outline dol = dbg.gameObject.AddComponent<Outline>(); dol.effectColor = new Color(0.2f, 0.7f, 0.8f, 0.6f); dol.effectDistance = new Vector2(2, -2);
        kp.display = UIText("Display", rt, "_ _ _ _ _ _", 54, new Vector2(0, 200), new Vector2(490, 90), new Color(0.35f, 0.95f, 1f), fontBold);

        RectTransform keysRt = UIRect("Keys", rt, Vector2.zero, new Vector2(580, 680));
        kp.keys = keysRt.gameObject.AddComponent<CanvasGroup>();

        Color keyFill = new Color(0.17f, 0.19f, 0.22f), keyText = new Color(1f, 0.78f, 0.4f);
        string[,] layout = { { "1", "2", "3" }, { "4", "5", "6" }, { "7", "8", "9" }, { "CLEAR", "0", "ENTER" } };
        for (int row = 0; row < 4; row++)
            for (int col = 0; col < 3; col++)
            {
                string label = layout[row, col];
                Vector2 pos = new Vector2((col - 1) * 165f, 85f - row * 90f);
                bool special = label.Length > 1;
                Color fill = label == "ENTER" ? new Color(0.08f, 0.36f, 0.18f) : label == "CLEAR" ? new Color(0.42f, 0.09f, 0.07f) : keyFill;
                Color tc = label == "ENTER" ? new Color(0.6f, 1f, 0.7f) : label == "CLEAR" ? new Color(1f, 0.6f, 0.55f) : keyText;
                Button b = UIButton("Key_" + label, keysRt, label, pos, new Vector2(150, 78), fill, tc, fontBold, special ? 26 : 38);
                if (label == "ENTER") kp.enterButton = b;
                else if (label == "CLEAR") kp.clearButton = b;
                else kp.digitButtons[int.Parse(label)] = b;
            }
        kp.closeButton = UIButton("Close", keysRt, "X", new Vector2(250, 292), new Vector2(60, 56), new Color(0.3f, 0.07f, 0.06f), new Color(1f, 0.6f, 0.5f), fontBold, 28);
        UIText("Hint", keysRt, "6 DIGITS  -  ENTER TO CONFIRM", 20, new Vector2(0, -270), new Vector2(500, 36), new Color(0.55f, 0.6f, 0.65f), fontMed);
        UIText("PedId", keysRt, "ACCESS PEDESTAL 0" + (index + 1), 18, new Vector2(0, -305), new Vector2(500, 30), new Color(0.4f, 0.45f, 0.5f), fontMed);

        // cover shown while the keypad is closed (also a button, so a VR controller can open it)
        RectTransform ov = UIRect("IdleOverlay", rt, new Vector2(0, -35), new Vector2(560, 590));
        Image oimg = ov.gameObject.AddComponent<Image>(); oimg.color = new Color(0.02f, 0.025f, 0.035f, 0.96f);
        Button ob = ov.gameObject.AddComponent<Button>(); ob.targetGraphic = oimg;
        UIText("Prompt", ov, "PRESS E", 70, new Vector2(0, 40), new Vector2(520, 100), new Color(1f, 0.65f, 0.25f), fontBold);
        UIText("Prompt2", ov, "OR POINT AND PULL THE TRIGGER", 24, new Vector2(0, -35), new Vector2(540, 40), new Color(0.7f, 0.75f, 0.8f), fontMed);
        kp.idleOverlay = ov.gameObject; kp.openButton = ob;
        return kp;
    }

    // =====================================================================================================
    //  CABLES (thick cables from every pedestal to a floor hub, and from the hub to the vault)
    // =====================================================================================================
    static void BuildCables(Transform parent, List<Transform> peds)
    {
        Transform c = new GameObject("Cables").transform; c.SetParent(parent, false);
        Vector3 hub = new Vector3(0f, 0f, 4.6f);
        GameObject hubGo = Prim(PrimitiveType.Cylinder, "CableHub", c, hub + new Vector3(0, 0.06f, 0), new Vector3(1.1f, 0.06f, 1.1f), darkSteel);
        Prim(PrimitiveType.Cylinder, "CableHubRing", c, hub + new Vector3(0, 0.125f, 0), new Vector3(0.8f, 0.006f, 0.8f), amberGlow);
        Prim(PrimitiveType.Cylinder, "CableHubCap", c, hub + new Vector3(0, 0.16f, 0), new Vector3(0.45f, 0.05f, 0.45f), black);
        foreach (Transform p in peds)
        {
            Vector3 start = p.position + p.rotation * new Vector3(0, 0.15f, 0.34f);
            Vector3 dir = (p.position - hub); dir.y = 0f; dir.Normalize();
            Vector3 end = hub + dir * 0.55f + new Vector3(0, 0.14f, 0);
            Vector3 ctrl = Vector3.Lerp(start, end, 0.5f) + new Vector3(0, -0.1f, 0) + (p.rotation * Vector3.forward) * 0.9f;
            Bezier(c, "Cable", start, ctrl, end, 0.055f);
        }
        // thick trunk from the hub to the base of the vault
        Bezier(c, "CableTrunk", hub + new Vector3(0, 0.1f, 0.5f), hub + new Vector3(0.4f, 0.05f, 1.7f), new Vector3(0f, 0.12f, 6.2f), 0.1f);
    }

    static void Bezier(Transform parent, string name, Vector3 a, Vector3 b, Vector3 c, float radius)
    {
        Transform g = new GameObject(name).transform; g.SetParent(parent, false);
        int n = 18; Vector3 prev = a;
        for (int i = 1; i <= n; i++)
        {
            float t = i / (float)n;
            Vector3 p = (1 - t) * (1 - t) * a + 2 * (1 - t) * t * b + t * t * c;
            p.y = Mathf.Max(p.y, radius);                                  // rests on the floor
            Segment(g, prev, p, radius, rubber);
            prev = p;
        }
    }

    static void Segment(Transform parent, Vector3 a, Vector3 b, float radius, Material m)
    {
        Vector3 d = b - a; float len = d.magnitude; if (len < 0.001f) return;
        GameObject s = GameObject.CreatePrimitive(PrimitiveType.Capsule);
        s.name = "Seg"; s.transform.SetParent(parent, false);
        s.transform.position = (a + b) * 0.5f;
        s.transform.rotation = Quaternion.FromToRotation(Vector3.up, d);
        s.transform.localScale = new Vector3(radius * 2f, len * 0.5f + radius, radius * 2f);
        s.GetComponent<Renderer>().sharedMaterial = m;
        Object.DestroyImmediate(s.GetComponent<Collider>());
    }

    // =====================================================================================================
    //  WORKSTATIONS  (side = -1 left: monitors, side = +1 right: card testing equipment)
    // =====================================================================================================
    static void BuildWorkstation(Transform parent, int side)
    {
        Transform ws = new GameObject(side < 0 ? "Workstation_Left_Monitors" : "Workstation_Right_CardTesting").transform;
        ws.SetParent(parent, false);
        float x = side * 5.0f, z = -4.4f;
        Quaternion face = Quaternion.LookRotation(new Vector3(side, 0, 0));        // local -Z looks to the room centre
        Transform desk = new GameObject("Desk").transform; desk.SetParent(ws, false);
        desk.position = new Vector3(x, 0, z); desk.rotation = face;
        // desk: local X is along the desk (3 m... ) ; local -Z is the front edge toward the room
        Prim(PrimitiveType.Cube, "Top", desk, new Vector3(0, 0.78f, 0), new Vector3(2.5f, 0.06f, 0.95f), steel);
        Prim(PrimitiveType.Cube, "Front", desk, new Vector3(0, 0.45f, -0.4f), new Vector3(2.4f, 0.6f, 0.03f), darkSteel);
        for (int i = -1; i <= 1; i += 2)
            Prim(PrimitiveType.Cube, "Leg", desk, new Vector3(i * 1.15f, 0.37f, 0.0f), new Vector3(0.08f, 0.74f, 0.85f), darkSteel);
        Prim(PrimitiveType.Cube, "Back", desk, new Vector3(0, 0.45f, 0.4f), new Vector3(2.4f, 0.6f, 0.03f), black);
        Prim(PrimitiveType.Cube, "Underlight", desk, new Vector3(0, 0.15f, -0.41f), new Vector3(2.2f, 0.02f, 0.02f), amberGlow);

        if (side < 0)
        {
            string[] lines = { "TEST SUITE  412/412\nPASS  PASS  PASS\nCOVERAGE 87%", "> run regression\nbuild 1.0.7 ... OK\nFAIL: login_03", "ASSERT total==100\nexpected 100\nactual   99" };
            for (int i = -1; i <= 1; i++)
            {
                Transform m = new GameObject("Monitor" + i).transform; m.SetParent(desk, false);
                m.localPosition = new Vector3(i * 0.78f, 1.2f, 0.12f);
                m.localRotation = Quaternion.Euler(0, -i * 14f, 0);
                Prim(PrimitiveType.Cube, "Panel", m, Vector3.zero, new Vector3(0.7f, 0.44f, 0.045f), black);
                Prim(PrimitiveType.Cube, "Screen", m, new Vector3(0, 0, -0.026f), new Vector3(0.64f, 0.38f, 0.004f), i == 0 ? screenGreen : screenBlue);
                TextMeshPro t = Text3D("Code", m, lines[i + 1], 0.8f, new Vector3(0, 0, -0.03f), Vector3.zero, new Color(0.02f, 0.08f, 0.1f), new Vector2(0.6f, 0.34f));
                t.alignment = TextAlignmentOptions.TopLeft; t.color = new Color(0.9f, 1f, 1f);
                Prim(PrimitiveType.Cube, "Neck", m, new Vector3(0, -0.3f, 0.06f), new Vector3(0.05f, 0.18f, 0.04f), darkSteel);
                Prim(PrimitiveType.Cube, "Foot", m, new Vector3(0, -0.4f, 0.0f), new Vector3(0.3f, 0.02f, 0.2f), darkSteel);
            }
            Prim(PrimitiveType.Cube, "Keyboard", desk, new Vector3(0, 0.815f, -0.2f), new Vector3(0.5f, 0.02f, 0.16f), black);
            Prim(PrimitiveType.Cube, "Tower", desk, new Vector3(1.0f, 0.97f, 0.1f), new Vector3(0.22f, 0.3f, 0.4f), darkSteel);
            Prim(PrimitiveType.Sphere, "TowerLed", desk, new Vector3(1.0f, 1.05f, -0.105f), Vector3.one * 0.02f, lampGreen);
        }
        else
        {
            // card testing equipment: a terminal with a card slot, display and keypad, a test harness and loose cards
            Transform t = new GameObject("CardTerminal").transform; t.SetParent(desk, false);
            t.localPosition = new Vector3(-0.5f, 0.81f, -0.1f); t.localRotation = Quaternion.Euler(0, 0, 0);
            Prim(PrimitiveType.Cube, "Base", t, new Vector3(0, 0.08f, 0), new Vector3(0.42f, 0.16f, 0.3f), darkSteel);
            Prim(PrimitiveType.Cube, "Head", t, new Vector3(0, 0.25f, 0.05f), new Vector3(0.36f, 0.24f, 0.12f), black);
            Prim(PrimitiveType.Cube, "Display", t, new Vector3(0, 0.27f, -0.012f), new Vector3(0.28f, 0.14f, 0.005f), screenGreen);
            TextMeshPro dt = Text3D("DisplayText", t, "CARD TEST\nINSERT CARD", 0.6f, new Vector3(0, 0.27f, -0.017f), Vector3.zero, new Color(0.9f, 1f, 0.95f), new Vector2(0.27f, 0.13f));
            Prim(PrimitiveType.Cube, "CardSlot", t, new Vector3(0, 0.17f, -0.16f), new Vector3(0.22f, 0.012f, 0.02f), black);
            for (int r = 0; r < 3; r++) for (int c = 0; c < 3; c++)
                Prim(PrimitiveType.Cube, "Key", t, new Vector3(-0.09f + c * 0.09f, 0.165f, -0.07f + r * -0.045f + 0.045f), new Vector3(0.07f, 0.012f, 0.035f), steel);
            Prim(PrimitiveType.Cube, "CardInSlot", t, new Vector3(0, 0.185f, -0.2f), new Vector3(0.17f, 0.004f, 0.11f), white);
            // test harness (rack unit with LEDs)
            Prim(PrimitiveType.Cube, "Harness", desk, new Vector3(0.45f, 0.92f, 0.1f), new Vector3(0.7f, 0.22f, 0.4f), darkSteel);
            for (int i = 0; i < 6; i++)
                Prim(PrimitiveType.Sphere, "Led" + i, desk, new Vector3(0.2f + i * 0.09f, 0.95f, -0.105f), Vector3.one * 0.022f, (i % 3 == 0) ? lampRed : (i % 3 == 1 ? lampGreen : amberGlow));
            Prim(PrimitiveType.Cube, "HarnessSlot", desk, new Vector3(0.45f, 0.9f, -0.1f), new Vector3(0.55f, 0.015f, 0.02f), black);
            // loose cards
            Color[] cc = { new Color(0.1f, 0.25f, 0.6f), new Color(0.6f, 0.1f, 0.1f), new Color(0.1f, 0.5f, 0.3f), new Color(0.7f, 0.55f, 0.1f) };
            for (int i = 0; i < cc.Length; i++)
            {
                GameObject card = Prim(PrimitiveType.Cube, "Card" + i, desk, new Vector3(-0.1f - i * 0.28f, 0.812f + i * 0.0035f, -0.28f + (i % 2) * 0.1f), new Vector3(0.17f, 0.003f, 0.108f), Flat("M_Card" + i, cc[i], 0.3f, 0.6f));
                card.transform.localRotation = Quaternion.Euler(0, 15f * (i - 1.5f), 0);
                Object.DestroyImmediate(card.GetComponent<Collider>());
            }
            Prim(PrimitiveType.Cube, "ScannerLeft", desk, new Vector3(-0.85f, 0.88f, 0.15f), new Vector3(0.22f, 0.18f, 0.22f), black);
            Prim(PrimitiveType.Sphere, "ScannerLed", desk, new Vector3(-0.85f, 0.93f, 0.038f), Vector3.one * 0.02f, lampGreen);
        }
        NewLight("DeskLight", ws, side < 0 ? new Color(0.4f, 0.7f, 1f) : new Color(0.6f, 1f, 0.8f), 4f, 4.5f).transform.position = new Vector3(x - side * 0.9f, 2.2f, z);
    }

    // =====================================================================================================
    //  CABINETS
    // =====================================================================================================
    static void BuildCabinets(Transform parent)
    {
        Transform c = new GameObject("Cabinets").transform; c.SetParent(parent, false);
        // along the entrance wall (behind the player) and both sides of the vault
        float[] xs = { -4.8f, -3.7f, -2.6f, 2.6f, 3.7f, 4.8f };
        foreach (float x in xs) Cabinet(c, new Vector3(x, 0, -8.45f), Quaternion.identity);
        Cabinet(c, new Vector3(-5.25f, 0, 8.45f), Quaternion.Euler(0, 180, 0));
        Cabinet(c, new Vector3(5.25f, 0, 8.45f), Quaternion.Euler(0, 180, 0));
        Cabinet(c, new Vector3(-4.15f, 0, 8.45f), Quaternion.Euler(0, 180, 0));
        Cabinet(c, new Vector3(4.15f, 0, 8.45f), Quaternion.Euler(0, 180, 0));
    }

    static void Cabinet(Transform parent, Vector3 pos, Quaternion rot)
    {
        Transform c = new GameObject("Cabinet").transform; c.SetParent(parent, false);
        c.SetPositionAndRotation(pos, rot);
        // local -Z is the front (rot identity means the front faces -Z; entrance wall cabinets are turned by the caller where needed)
        Quaternion fix = Quaternion.Euler(0, 180, 0);
        c.rotation = rot * fix;                           // front now faces +Z for rot = identity (toward the room from the entrance wall)
        Prim(PrimitiveType.Cube, "Body", c, new Vector3(0, 1.05f, 0), new Vector3(1.0f, 2.1f, 0.55f), darkSteel);
        Prim(PrimitiveType.Cube, "DoorL", c, new Vector3(-0.24f, 1.05f, -0.285f), new Vector3(0.46f, 1.95f, 0.02f), steel);
        Prim(PrimitiveType.Cube, "DoorR", c, new Vector3(0.24f, 1.05f, -0.285f), new Vector3(0.46f, 1.95f, 0.02f), steel);
        Prim(PrimitiveType.Cube, "HandleL", c, new Vector3(-0.04f, 1.05f, -0.31f), new Vector3(0.025f, 0.35f, 0.025f), black);
        Prim(PrimitiveType.Cube, "HandleR", c, new Vector3(0.04f, 1.05f, -0.31f), new Vector3(0.025f, 0.35f, 0.025f), black);
        for (int i = 0; i < 5; i++) Prim(PrimitiveType.Cube, "Vent", c, new Vector3(0, 1.75f + i * 0.03f, -0.298f), new Vector3(0.7f, 0.012f, 0.004f), black);
        Prim(PrimitiveType.Sphere, "Led", c, new Vector3(0.4f, 1.9f, -0.3f), Vector3.one * 0.03f, (Mathf.Abs(pos.x) > 4f) ? lampGreen : amberGlow);
    }

    // =====================================================================================================
    //  L2 - placeholder scenes for the three destinations
    // =====================================================================================================
    [MenuItem("The Last Protocol/L2 - Create Room Placeholder Scenes")]
    static void CreatePlaceholders()
    {
        if (!EditorSceneManager.SaveCurrentModifiedScenesIfUserWantsTo()) return;
        LoadAssets();
        GameObject rigPrefab = FindPrefab("XR Origin Hands (XR Rig)");
        List<string> made = new List<string>();
        for (int i = 0; i < 3; i++)
        {
            string path = "Assets/Scenes 1/" + RoomScenes[i] + ".unity";
            if (File.Exists(path)) { made.Add(path); continue; }                  // never overwrite
            Scene s = EditorSceneManager.NewScene(NewSceneSetup.DefaultGameObjects, NewSceneMode.Single);
            if (rigPrefab != null)
            {
                foreach (GameObject go in s.GetRootGameObjects()) if (go.name == "Main Camera") Object.DestroyImmediate(go);
                GameObject rig = (GameObject)PrefabUtility.InstantiatePrefab(rigPrefab);
                rig.transform.position = Vector3.zero;
                Transform loco = Deep(rig.transform, "Locomotion"); if (loco != null) loco.gameObject.SetActive(false);
            }
            GameObject t = new GameObject("PlaceholderText");
            t.transform.position = new Vector3(0, 1.6f, 3f);
            TextMeshPro tm = t.AddComponent<TextMeshPro>();
            tm.text = RoomNames[i] + "\nPLACEHOLDER ROOM\n\nPress Esc to return to the lab";
            tm.fontSize = 4; tm.alignment = TextAlignmentOptions.Center; tm.color = new Color(1f, 0.65f, 0.25f);
            tm.rectTransform.sizeDelta = new Vector2(8f, 3f);
            ReturnToScene rts = t.AddComponent<ReturnToScene>(); rts.sceneName = "EscapeRoom_Main"; rts.autoReturnSeconds = 0f;
            EditorSceneManager.SaveScene(s, path);
            made.Add(path);
        }
        List<string> all = new List<string> { MenuScene, LabScene }; all.AddRange(made);
        AddToBuildSettings(all.ToArray());
        EditorSceneManager.OpenScene(LabScene, OpenSceneMode.Single);
        Debug.Log("Placeholder rooms ready and added to Build Settings: Room_Alpha, Room_Beta, Room_Gamma. EscapeRoom_Main is open again.");
    }

    static void AddToBuildSettings(params string[] paths)
    {
        List<EditorBuildSettingsScene> list = new List<EditorBuildSettingsScene>(EditorBuildSettings.scenes);
        foreach (string p in paths)
        {
            bool found = false;
            foreach (EditorBuildSettingsScene s in list) if (s.path == p) { s.enabled = true; found = true; }
            if (!found) list.Add(new EditorBuildSettingsScene(p, true));
        }
        EditorBuildSettings.scenes = list.ToArray();
    }

    // =====================================================================================================
    //  small helpers
    // =====================================================================================================
    static Transform FindAny(string name)
    {
        foreach (Transform t in Resources.FindObjectsOfTypeAll<Transform>())
            if (t != null && t.name == name && t.gameObject.scene.IsValid()) return t;
        return null;
    }

    static Transform Deep(Transform root, string name)
    {
        foreach (Transform c in root)
        {
            if (c.name == name) return c;
            Transform r = Deep(c, name);
            if (r != null) return r;
        }
        return null;
    }

    static GameObject FindPrefab(string name)
    {
        string fallback = null;
        foreach (string guid in AssetDatabase.FindAssets(name + " t:Prefab"))
        {
            string p = AssetDatabase.GUIDToAssetPath(guid);
            if (Path.GetFileNameWithoutExtension(p) != name) continue;
            if (p.StartsWith("Assets/Samples/")) return AssetDatabase.LoadAssetAtPath<GameObject>(p);
            if (fallback == null) fallback = p;
        }
        return fallback != null ? AssetDatabase.LoadAssetAtPath<GameObject>(fallback) : null;
    }

    static AudioClip Clip(string name)
    {
        foreach (string guid in AssetDatabase.FindAssets(name + " t:AudioClip"))
        {
            string p = AssetDatabase.GUIDToAssetPath(guid);
            if (Path.GetFileNameWithoutExtension(p) == name) return AssetDatabase.LoadAssetAtPath<AudioClip>(p);
        }
        return null;
    }

    // two short low buzzes (made in code, no download needed)
    static AudioClip MakeDeniedClip()
    {
        if (!AssetDatabase.IsValidFolder(Gen + "/Audio")) AssetDatabase.CreateFolder(Gen, "Audio");
        string path = Gen + "/Audio/sfx_denied.wav";
        if (!File.Exists(path))
        {
            const int rate = 44100; float[] d = new float[(int)(rate * 0.7f)];
            for (int i = 0; i < d.Length; i++)
            {
                float t = i / (float)rate;
                float burst = (t < 0.22f || (t > 0.32f && t < 0.54f)) ? 1f : 0f;
                float env = burst * (1f - Mathf.Repeat(t, 0.32f) / 0.32f * 0.3f);
                float sq = Mathf.Sign(Mathf.Sin(2f * Mathf.PI * 150f * t)) * 0.5f + Mathf.Sin(2f * Mathf.PI * 300f * t) * 0.3f;
                d[i] = sq * env * 0.5f;
            }
            using (FileStream fs = new FileStream(path, FileMode.Create))
            using (BinaryWriter w = new BinaryWriter(fs))
            {
                int bytes = d.Length * 2;
                w.Write(new[] { 'R', 'I', 'F', 'F' }); w.Write(36 + bytes); w.Write(new[] { 'W', 'A', 'V', 'E' });
                w.Write(new[] { 'f', 'm', 't', ' ' }); w.Write(16); w.Write((short)1); w.Write((short)1);
                w.Write(rate); w.Write(rate * 2); w.Write((short)2); w.Write((short)16);
                w.Write(new[] { 'd', 'a', 't', 'a' }); w.Write(bytes);
                foreach (float f in d) w.Write((short)(Mathf.Clamp(f, -1f, 1f) * 30000f));
            }
            AssetDatabase.ImportAsset(path, ImportAssetOptions.ForceUpdate);
        }
        return AssetDatabase.LoadAssetAtPath<AudioClip>(path);
    }

    static Material LoadMat(string name)
    {
        Material m = AssetDatabase.LoadAssetAtPath<Material>(Mats + "/" + name + ".mat");
        return m != null ? m : Flat(name, new Color(0.3f, 0.3f, 0.33f), 0.6f, 0.5f);
    }

    static Material Flat(string name, Color c, float metal, float smooth)
    {
        string path = Mats + "/" + name + ".mat";
        Material m = AssetDatabase.LoadAssetAtPath<Material>(path);
        if (m == null) { m = new Material(Shader.Find("Universal Render Pipeline/Lit")); AssetDatabase.CreateAsset(m, path); }
        m.SetColor("_BaseColor", c); m.SetFloat("_Metallic", metal); m.SetFloat("_Smoothness", smooth);
        EditorUtility.SetDirty(m);
        return m;
    }

    static Material Emissive(string name, Color baseC, Color emission)
    {
        Material m = Flat(name, baseC, 0f, 0.3f);
        m.EnableKeyword("_EMISSION");
        m.SetColor("_EmissionColor", emission);
        m.globalIlluminationFlags = MaterialGlobalIlluminationFlags.None;
        EditorUtility.SetDirty(m);
        return m;
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

    static Light NewLight(string name, Transform parent, Color color, float intensity, float range)
    {
        GameObject go = new GameObject(name);
        go.transform.SetParent(parent, false);
        Light l = go.AddComponent<Light>();
        l.type = LightType.Point; l.color = color; l.intensity = intensity; l.range = range; l.shadows = LightShadows.None;
        return l;
    }

    static TextMeshPro Text3D(string name, Transform parent, string text, float size, Vector3 localPos, Vector3 euler, Color color, Vector2 box)
    {
        GameObject go = new GameObject(name);
        go.transform.SetParent(parent, false);
        go.transform.localPosition = localPos; go.transform.localEulerAngles = euler;
        TextMeshPro t = go.AddComponent<TextMeshPro>();
        t.text = text; t.fontSize = size; t.color = color; t.alignment = TextAlignmentOptions.Center;
        t.rectTransform.sizeDelta = box;
        if (fontMed != null) t.font = fontMed;
        return t;
    }

    static RectTransform UIRect(string name, Transform parent, Vector2 pos, Vector2 size)
    {
        GameObject go = new GameObject(name, typeof(RectTransform));
        go.transform.SetParent(parent, false);
        RectTransform rt = (RectTransform)go.transform;
        rt.anchorMin = rt.anchorMax = rt.pivot = new Vector2(0.5f, 0.5f);
        rt.anchoredPosition = pos; rt.sizeDelta = size;
        return rt;
    }

    static TextMeshProUGUI UIText(string name, Transform parent, string text, float size, Vector2 pos, Vector2 box, Color c, TMP_FontAsset f)
    {
        RectTransform rt = UIRect(name, parent, pos, box);
        TextMeshProUGUI t = rt.gameObject.AddComponent<TextMeshProUGUI>();
        t.text = text; t.fontSize = size; t.color = c; t.alignment = TextAlignmentOptions.Center; t.raycastTarget = false;
        t.overflowMode = TextOverflowModes.Overflow;
        if (f != null) t.font = f;
        return t;
    }

    static Button UIButton(string name, Transform parent, string label, Vector2 pos, Vector2 size, Color fill, Color text, TMP_FontAsset f, float fontSize)
    {
        RectTransform rt = UIRect(name, parent, pos, size);
        Image img = rt.gameObject.AddComponent<Image>(); img.color = fill;
        Outline o = rt.gameObject.AddComponent<Outline>(); o.effectColor = new Color(1f, 0.6f, 0.2f, 0.45f); o.effectDistance = new Vector2(2, -2);
        Button b = rt.gameObject.AddComponent<Button>(); b.targetGraphic = img;
        ColorBlock cb = b.colors;
        cb.normalColor = new Color(0.85f, 0.85f, 0.88f); cb.highlightedColor = Color.white;
        cb.pressedColor = new Color(0.55f, 0.55f, 0.6f); cb.selectedColor = cb.normalColor; cb.disabledColor = new Color(0.3f, 0.3f, 0.3f);
        cb.fadeDuration = 0.08f;
        b.colors = cb;
        UIText("Label", rt, label, fontSize, Vector2.zero, size, text, f);
        return b;
    }
}
#endif
