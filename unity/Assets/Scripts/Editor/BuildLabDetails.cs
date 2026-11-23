#if UNITY_EDITOR
using System.Collections.Generic;
using System.IO;
using TMPro;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.Rendering;
using UnityEngine.Rendering.Universal;
using UnityEngine.SceneManagement;

// Menu: The Last Protocol > L4 - Add Testing Details + Lighting
// Adds, on top of the built lab: old rusty / riveted metal, grime and puddles, lots of wires on the floor and
// hanging from the ceiling, software-testing props (test papers on the floor, bug reports, checklists, boards,
// sticky notes, bugs in jars, labelled boxes, floor stencils) and a brighter, better balanced lighting setup.
// Safe to run again: it replaces its own objects.
public static partial class BuildEscapeRoom
{
    static Texture2D texSeams, texRust, texGrime, texPuddle, texHazard, texPaperA, texPaperB, texPaperC, texPaperD, texBoard;
    static Material matWire, matWireGrey, matWireRed, matWireBlue, matWireYellow, matGlass, matStickyY, matStickyP, matStickyC, matBugRed, matBugGreen, matBoardWhite, matCork;

    [MenuItem("The Last Protocol/L4 - Add Testing Details + Lighting")]
    static void AddDetails()
    {
        if (SceneManager.GetActiveScene().path != LabScene)
        { EditorUtility.DisplayDialog("Open EscapeRoom_Main", "Open Assets/Scenes 1/EscapeRoom_Main.unity and run this again.", "OK"); return; }

        LoadAssets();
        MakeRefAssets();
        MakeDetailAssets();

        foreach (string n in new[] { "TestingDetails", "LabFillLights" })
        { Transform old = FindAny(n); if (old != null) Object.DestroyImmediate(old.gameObject); }

        Transform root = new GameObject("TestingDetails").transform;
        Random.InitState(11);
        BuildWear(root);
        BuildFloorWires(root);
        BuildTestingProps(root);
        foreach (Collider c in root.GetComponentsInChildren<Collider>()) Object.DestroyImmediate(c);
        BuildLabLighting();

        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        EditorSceneManager.SaveScene(SceneManager.GetActiveScene());
        Debug.Log("Details and lighting added and scene saved. Press Play.");
    }

    // =====================================================================================================
    //  LIGHTING
    // =====================================================================================================
    static void BuildLabLighting()
    {
        RenderSettings.ambientMode = AmbientMode.Flat;
        RenderSettings.ambientLight = new Color(0.085f, 0.095f, 0.125f);
        RenderSettings.fog = true;
        RenderSettings.fogMode = FogMode.ExponentialSquared;
        RenderSettings.fogColor = new Color(0.05f, 0.06f, 0.08f);
        RenderSettings.fogDensity = 0.013f;

        Transform g = new GameObject("LabFillLights").transform;
        // cool fill from the sides, warm glow toward the vault, soft light over the foreground tables
        Vector3[] cool = { new Vector3(-3.8f, 3.4f, -3.5f), new Vector3(3.8f, 3.4f, -3.5f), new Vector3(-3.8f, 3.4f, 2.5f), new Vector3(3.8f, 3.4f, 2.5f), new Vector3(0, 4.2f, -6.5f) };
        foreach (Vector3 p in cool) NewLight("CoolFill", g, new Color(0.55f, 0.7f, 1f), 7f, 10f).transform.position = p;
        NewLight("VaultWarm", g, new Color(1f, 0.7f, 0.4f), 9f, 9f).transform.position = new Vector3(0, 1.6f, 6.0f);
        NewLight("DaisWarm", g, new Color(1f, 0.65f, 0.35f), 6f, 7f).transform.position = new Vector3(0, 2.6f, 0.2f);

        // brighter post-processing: its own copy of the profile so the main menu keeps its look
        string src = Gen + "/MenuVolumeProfile.asset", dst = Gen + "/LabVolumeProfile.asset";
        if (File.Exists(src))
        {
            if (!File.Exists(dst)) AssetDatabase.CopyAsset(src, dst);
            VolumeProfile prof = AssetDatabase.LoadAssetAtPath<VolumeProfile>(dst);
            Volume vol = Object.FindFirstObjectByType<Volume>();
            if (prof != null && vol != null)
            {
                vol.sharedProfile = prof;
                if (prof.TryGet(out ColorAdjustments ca)) { ca.postExposure.Override(0.55f); ca.contrast.Override(14f); }
                if (prof.TryGet(out Vignette vg)) vg.intensity.Override(0.3f);
                if (prof.TryGet(out Bloom bl)) bl.intensity.Override(1.3f);
                EditorUtility.SetDirty(prof);
            }
        }
        ReflectionProbe probe = Object.FindFirstObjectByType<ReflectionProbe>();
        if (probe != null) probe.intensity = 1.4f;
    }

    // =====================================================================================================
    //  OLD METAL: riveted panels, rust, grime, puddles, hazard tape
    // =====================================================================================================
    static void BuildWear(Transform parent)
    {
        Transform g = new GameObject("Wear").transform; g.SetParent(parent, false);

        // riveted panel seams over each wall (one big transparent quad per wall, tiled)
        Wall(g, "Seams_Left", new Vector3(-5.994f, 3.0f, 0f), -90f, new Vector2(18f, 6f), texSeams, 1.2f, new Color(1, 1, 1, 1f));
        Wall(g, "Seams_Right", new Vector3(5.994f, 3.0f, 0f), 90f, new Vector2(18f, 6f), texSeams, 1.2f, new Color(1, 1, 1, 1f));
        Wall(g, "Seams_Back", new Vector3(0f, 3.0f, 8.994f), 0f, new Vector2(12f, 6f), texSeams, 1.2f, new Color(1, 1, 1, 1f));
        Wall(g, "Seams_Front", new Vector3(0f, 3.0f, -8.994f), 180f, new Vector2(12f, 6f), texSeams, 1.2f, new Color(1, 1, 1, 1f));

        // rust patches and grime streaks scattered on the walls
        for (int i = 0; i < 22; i++)
        {
            int wall = Random.Range(0, 4);
            Vector3 p; float yaw;
            float along = Random.Range(-1f, 1f);
            if (wall == 0) { p = new Vector3(-5.98f, 0, along * 8f); yaw = -90f; }
            else if (wall == 1) { p = new Vector3(5.98f, 0, along * 8f); yaw = 90f; }
            else if (wall == 2) { p = new Vector3(along * 5f, 0, 8.98f); yaw = 0f; }
            else { p = new Vector3(along * 5f, 0, -8.98f); yaw = 180f; }
            bool grime = i % 2 == 0;
            p.y = grime ? Random.Range(2.2f, 3.6f) : Random.Range(0.6f, 4.5f);
            Vector2 size = grime ? new Vector2(Random.Range(0.5f, 1.0f), Random.Range(2.5f, 4.5f)) : new Vector2(Random.Range(0.8f, 2.0f), Random.Range(0.6f, 1.6f));
            Decal(g, grime ? "Grime" : "Rust", p, Quaternion.Euler(0, yaw, Random.Range(-4f, 4f)), size, grime ? texGrime : texRust, grime ? new Color(1, 1, 1, 0.85f) : new Color(1, 1, 1, 0.8f), 0.15f);
        }

        // floor: dark glossy puddles, oil stains, hazard lane to the dais, stencil text
        Vector3[] pud = { new Vector3(-2.2f, 0, -2.8f), new Vector3(2.6f, 0, -1.6f), new Vector3(-0.8f, 0, 3.4f), new Vector3(3.1f, 0, 5.2f), new Vector3(-3.4f, 0, 5.8f), new Vector3(0.6f, 0, -5.4f), new Vector3(-4.0f, 0, -0.6f) };
        for (int i = 0; i < pud.Length; i++)
            Decal(g, "Puddle", new Vector3(pud[i].x, 0.006f + i * 0.0003f, pud[i].z), Quaternion.Euler(90, Random.Range(0, 360), 0), new Vector2(Random.Range(1.2f, 2.4f), Random.Range(0.8f, 1.6f)), texPuddle, new Color(0.02f, 0.03f, 0.04f, 0.8f), 1f);
        for (int i = 0; i < 10; i++)
            Decal(g, "Stain", new Vector3(Random.Range(-5f, 5f), 0.004f + i * 0.0002f, Random.Range(-7f, 7f)), Quaternion.Euler(90, Random.Range(0, 360), 0), Vector2.one * Random.Range(0.5f, 1.4f), texRust, new Color(0.3f, 0.2f, 0.12f, 0.45f), 0.1f);

        foreach (int sx in new[] { -1, 1 })
            Decal(g, "HazardLane", new Vector3(sx * 0.95f, 0.008f, -3.6f), Quaternion.Euler(90, 90, 0), new Vector2(3.0f, 0.22f), texHazard, Color.white, 0.2f);
        Decal(g, "HazardBar", new Vector3(0f, 0.008f, -2.4f), Quaternion.Euler(90, 0, 0), new Vector2(2.2f, 0.22f), texHazard, Color.white, 0.2f);
        FloorText(g, "TEST ZONE 01", new Vector3(0, 0.01f, -4.2f), 0f, 2.6f, new Color(1f, 0.8f, 0.15f));
        FloorText(g, "DO NOT SHIP UNTESTED", new Vector3(0, 0.01f, 4.0f), 0f, 1.6f, new Color(1f, 0.8f, 0.15f));
        FloorText(g, "^ EXIT PROTOCOL ^", new Vector3(0, 0.01f, 6.1f), 0f, 1.8f, new Color(1f, 0.55f, 0.15f));
    }

    static void Wall(Transform parent, string name, Vector3 pos, float yaw, Vector2 size, Texture2D tex, float tile, Color tint)
    {
        GameObject q = GameObject.CreatePrimitive(PrimitiveType.Quad);
        q.name = name; q.transform.SetParent(parent, false);
        q.transform.position = pos; q.transform.rotation = Quaternion.Euler(0, yaw, 0);
        q.transform.localScale = new Vector3(size.x, size.y, 1f);
        Material m = DecalMat("M_" + name, tex, tint, 0.3f);
        m.SetTextureScale("_BaseMap", new Vector2(size.x / tile, size.y / tile));
        q.GetComponent<Renderer>().sharedMaterial = m;
        q.GetComponent<Renderer>().shadowCastingMode = ShadowCastingMode.Off;
    }

    static void Decal(Transform parent, string name, Vector3 pos, Quaternion rot, Vector2 size, Texture2D tex, Color tint, float smooth)
    {
        GameObject q = GameObject.CreatePrimitive(PrimitiveType.Quad);
        q.name = name; q.transform.SetParent(parent, false);
        q.transform.position = pos; q.transform.rotation = rot;
        q.transform.localScale = new Vector3(size.x, size.y, 1f);
        q.GetComponent<Renderer>().sharedMaterial = DecalMat("M_Decal_" + tex.name + "_" + Mathf.RoundToInt(tint.a * 100) + "_" + Mathf.RoundToInt(smooth * 100) + "_" + ColorKey(tint), tex, tint, smooth);
        q.GetComponent<Renderer>().shadowCastingMode = ShadowCastingMode.Off;
    }

    static string ColorKey(Color c) { return Mathf.RoundToInt(c.r * 9) + "" + Mathf.RoundToInt(c.g * 9) + "" + Mathf.RoundToInt(c.b * 9); }

    // see-through painted layer (rust, grime ...) that still reacts to light
    static Material DecalMat(string name, Texture2D tex, Color tint, float smooth)
    {
        string path = Mats + "/" + name + ".mat";
        Material m = AssetDatabase.LoadAssetAtPath<Material>(path);
        if (m == null) { m = new Material(Shader.Find("Universal Render Pipeline/Lit")); AssetDatabase.CreateAsset(m, path); }
        m.SetFloat("_Surface", 1f); m.SetFloat("_Blend", 0f);
        m.SetFloat("_SrcBlend", (float)BlendMode.SrcAlpha); m.SetFloat("_DstBlend", (float)BlendMode.OneMinusSrcAlpha);
        m.SetFloat("_SrcBlendAlpha", (float)BlendMode.One); m.SetFloat("_DstBlendAlpha", (float)BlendMode.OneMinusSrcAlpha);
        m.SetFloat("_ZWrite", 0f);
        m.EnableKeyword("_SURFACE_TYPE_TRANSPARENT");
        m.SetOverrideTag("RenderType", "Transparent");
        m.renderQueue = 3000;
        m.SetTexture("_BaseMap", tex);
        m.SetColor("_BaseColor", tint);
        m.SetFloat("_Smoothness", smooth); m.SetFloat("_Metallic", 0f);
        EditorUtility.SetDirty(m);
        return m;
    }

    static void FloorText(Transform parent, string text, Vector3 pos, float yaw, float size, Color c)
    {
        TextMeshPro t = Text3D("Stencil", parent, text, size, pos, new Vector3(90f, yaw, 0f), c, new Vector2(6f, 1f));
        if (fontBold != null) t.font = fontBold;
        t.color = new Color(c.r, c.g, c.b, 0.55f);
    }

    // =====================================================================================================
    //  WIRES
    // =====================================================================================================
    static void Wire(Transform parent, string name, Vector3 a, Vector3 b, Vector3 c, float radius, Material m, int n = 12)
    {
        Transform g = new GameObject(name).transform; g.SetParent(parent, false);
        Vector3 prev = a;
        for (int i = 1; i <= n; i++)
        {
            float t = i / (float)n;
            Vector3 p = (1 - t) * (1 - t) * a + 2 * (1 - t) * t * b + t * t * c;
            if (p.y < 6f && a.y < 1f) p.y = Mathf.Max(p.y, radius);
            Segment(g, prev, p, radius, m);
            prev = p;
        }
    }

    static void Coil(Transform parent, string name, Vector3 pos, float r0, int loops, float radius, Material m)
    {
        Transform g = new GameObject(name).transform; g.SetParent(parent, false);
        int steps = loops * 14;
        Vector3 prev = pos + new Vector3(r0, radius, 0);
        for (int i = 1; i <= steps; i++)
        {
            float a = i / 14f * Mathf.PI * 2f;
            float r = Mathf.Lerp(r0, r0 * 0.45f, i / (float)steps);
            Vector3 p = pos + new Vector3(Mathf.Cos(a) * r, radius + (i / (float)steps) * radius * 2.2f * loops * 0.5f, Mathf.Sin(a) * r);
            Segment(g, prev, p, radius, m);
            prev = p;
        }
        Wire(g, "Tail", prev, prev + new Vector3(0.4f, 0f, 0.3f), prev + new Vector3(1.0f, -1f, 0.2f), radius, m, 8);
    }

    static void BuildFloorWires(Transform parent)
    {
        Transform g = new GameObject("FloorWires").transform; g.SetParent(parent, false);
        Material[] mats = { matWire, matWire, matWire, matWireGrey, matWireRed, matWireBlue, matWireYellow };
        Vector3[] A = {
            new Vector3(-4.8f,0.1f,-2.0f), new Vector3(-4.7f,0.1f,0.9f), new Vector3(-4.7f,0.1f,2.4f), new Vector3(-4.2f,0.1f,4.3f),
            new Vector3(-4.2f,0.1f,6.2f), new Vector3(4.7f,0.1f,-0.1f), new Vector3(4.7f,0.1f,2.2f), new Vector3(4.7f,0.1f,3.9f),
            new Vector3(4.2f,0.1f,4.5f), new Vector3(4.2f,0.1f,6.3f), new Vector3(-2.4f,0.1f,0.2f), new Vector3(2.4f,0.1f,0.2f),
            new Vector3(0f,0.1f,3.0f), new Vector3(0f,0.1f,-2.3f), new Vector3(-1f,0.1f,6.8f), new Vector3(1f,0.1f,6.8f) };
        int[,] pairs = { {0,10},{1,10},{2,13},{3,12},{4,14},{5,11},{6,11},{7,12},{8,15},{9,15},{0,1},{5,6},{10,13},{11,13},{3,4},{7,8},{2,12},{6,15},{1,3},{9,7} };
        for (int i = 0; i < pairs.GetLength(0); i++)
        {
            Vector3 a = A[pairs[i, 0]], b = A[pairs[i, 1]];
            Vector3 mid = (a + b) * 0.5f + new Vector3(Random.Range(-1.4f, 1.4f), 0f, Random.Range(-1.4f, 1.4f));
            Material m = mats[Random.Range(0, mats.Length)];
            Wire(g, "Wire" + i, a, mid, b, Random.Range(0.022f, 0.05f), m);
            if (i % 3 == 0)            // a thin twin wire alongside
                Wire(g, "WireTwin" + i, a + new Vector3(0.06f, 0, 0.04f), mid + new Vector3(0.08f, 0, 0.1f), b + new Vector3(0.05f, 0, -0.05f), 0.016f, mats[Random.Range(3, mats.Length)]);
        }
        Coil(g, "Coil1", new Vector3(-3.3f, 0f, -3.6f), 0.38f, 4, 0.04f, matWire);
        Coil(g, "Coil2", new Vector3(3.3f, 0f, -2.2f), 0.32f, 3, 0.035f, matWireGrey);
        Coil(g, "Coil3", new Vector3(-2.6f, 0f, 5.2f), 0.3f, 3, 0.035f, matWire);
        Coil(g, "Coil4", new Vector3(3.0f, 0f, 6.2f), 0.4f, 4, 0.045f, matWire);

        // cables hanging in loops from the ceiling
        for (int i = 0; i < 7; i++)
        {
            float x = Random.Range(-4f, 4f), z = -7f + i * 2.2f;
            Wire(g, "Drape" + i, new Vector3(x, 6.1f, z), new Vector3(x + Random.Range(-0.6f, 0.6f), 3.2f + Random.Range(0f, 0.8f), z), new Vector3(x + Random.Range(1f, 2f), 6.1f, z), 0.04f, matWire, 14);
        }
    }

    // =====================================================================================================
    //  SOFTWARE-TESTING PROPS
    // =====================================================================================================
    static readonly string[] PaperTexts = {
        "TEST CASE TC-017\nLogin, wrong password\nEXPECTED: error shown\nACTUAL: app crashes\nRESULT: FAIL",
        "BUG #1042\nSEVERITY: HIGH\n1. Open settings\n2. Press Save twice\n=> crash on save",
        "REGRESSION CHECKLIST\n[x] login   [x] logout\n[ ] payment [x] search\n[ ] edge cases",
        "BOUNDARY VALUES\nvalid range 1..100\ntest 0, 1, 100, 101\nonly 101 failed",
        "UNIT TESTS  412\nPASSED  409\nFAILED    3\nCOVERAGE 87%",
        "TODO\ntest the vault door\ntest the test\nretest everything",
        "EQUIVALENCE CLASSES\nage < 0   invalid\n0..17    minor\n18+      adult",
        "BUG REPORT\nWorks on my machine\nSTATUS: REJECTED\nREASON: not reproducible" };

    static void BuildTestingProps(Transform parent)
    {
        Transform g = new GameObject("Testing").transform; g.SetParent(parent, false);
        Material[] pm = { matPaper, matPaper2, TexLit("M_PaperB", texPaperB, Color.white), TexLit("M_PaperC", texPaperC, Color.white), TexLit("M_PaperD", texPaperD, Color.white), TexLit("M_PaperA", texPaperA, Color.white) };

        // 1. papers scattered over the floor around the dais, the tables and the desks
        Vector2[] spots = {
            new Vector2(-2.6f,-1.2f), new Vector2(-3.1f,-0.4f), new Vector2(2.9f,-1.0f), new Vector2(3.4f,-0.2f), new Vector2(-1.2f,-2.9f), new Vector2(1.4f,-3.1f),
            new Vector2(-3.9f,-3.9f), new Vector2(3.6f,-4.1f), new Vector2(-2.2f,2.9f), new Vector2(2.2f,3.1f), new Vector2(-4.2f,2.1f), new Vector2(4.0f,-1.4f),
            new Vector2(-0.4f,-5.6f), new Vector2(0.9f,-5.9f), new Vector2(-3.2f,-5.1f), new Vector2(3.0f,-5.3f), new Vector2(-1.8f,4.6f), new Vector2(1.7f,5.0f),
            new Vector2(-4.5f,-1.6f), new Vector2(4.3f,2.8f), new Vector2(0.3f,2.1f), new Vector2(-0.9f,-1.6f) };
        for (int i = 0; i < spots.Length; i++)
        {
            Vector3 p = new Vector3(spots[i].x + Random.Range(-0.2f, 0.2f), 0.007f + i * 0.0006f, spots[i].y + Random.Range(-0.2f, 0.2f));
            float yaw = Random.Range(0f, 360f);
            if (i % 3 == 0) TextPaper(g, p, yaw, PaperTexts[(i / 3) % PaperTexts.Length]);
            else Paper(g, p, yaw, pm[i % pm.Length]);
        }
        // small crumpled balls of paper
        for (int i = 0; i < 8; i++)
        {
            GameObject b = Prim(PrimitiveType.Sphere, "PaperBall", g, new Vector3(Random.Range(-4f, 4f), 0.05f, Random.Range(-6f, 5f)), new Vector3(0.1f, 0.08f, 0.1f), matPaper);
            b.transform.rotation = Random.rotation;
        }

        // 2. test plan whiteboard (left wall, near the entrance) and corkboard + status board (right wall)
        Board(g, "Whiteboard", new Vector3(-5.93f, 2.35f, -6.0f), -90f, new Vector2(2.2f, 1.4f), matBoardWhite);
        Decal(g, "TestPyramid", new Vector3(-5.89f, 2.2f, -6.55f), Quaternion.Euler(0, -90, 0), new Vector2(1.0f, 0.86f), texBoard, Color.white, 0.4f);
        BoardText(g, new Vector3(-5.885f, 2.82f, -6.0f), -90f, "TEST PLAN", 2.4f, new Color(0.1f, 0.25f, 0.7f));
        BoardText(g, new Vector3(-5.885f, 2.2f, -5.5f), -90f, "UNIT\nINTEGRATION\nSYSTEM\nACCEPTANCE", 1.0f, new Color(0.05f, 0.05f, 0.12f));
        BoardText(g, new Vector3(-5.885f, 1.78f, -6.0f), -90f, "ISOLATE > REPRODUCE\n> FIX > RETEST", 0.9f, new Color(0.7f, 0.1f, 0.1f));

        Board(g, "Corkboard", new Vector3(5.93f, 2.35f, -6.0f), 90f, new Vector2(2.2f, 1.4f), matCork);
        Color[] sticky = { new Color(1f, 0.9f, 0.3f), new Color(1f, 0.55f, 0.75f), new Color(0.5f, 0.9f, 1f), new Color(0.6f, 1f, 0.5f) };
        string[] notes = { "BUG!", "RETEST", "EDGE CASE?", "FLAKY", "NULL?", "FIXED", "WHY?", "TODO" };
        for (int i = 0; i < 8; i++)
        {
            float z = -6.0f + (i % 4 - 1.5f) * 0.45f, y = 2.75f - (i / 4) * 0.55f;
            Sticky(g, new Vector3(5.89f, y, z), 90f, sticky[i % 4], notes[i], Random.Range(-8f, 8f));
        }
        Wire(g, "RedString", new Vector3(5.885f, 2.75f, -6.7f), new Vector3(5.885f, 2.4f, -6.0f), new Vector3(5.885f, 2.2f, -5.4f), 0.006f, matWireRed, 6);

        // status board over the right counter
        Prim(PrimitiveType.Cube, "StatusFrame", g, new Vector3(5.95f, 2.6f, 3.0f), new Vector3(0.06f, 0.8f, 2.4f), black);
        Prim(PrimitiveType.Cube, "StatusScreen", g, new Vector3(5.915f, 2.6f, 3.0f), new Vector3(0.01f, 0.7f, 2.3f), screenGreen);
        TextMeshPro st = Text3D("StatusText", g, "BUILD 1.0.7   TESTS RUN  1284\nPASSED 1279     FAILED 5     STATUS: UNSTABLE", 1.1f, new Vector3(5.905f, 2.6f, 3.0f), new Vector3(0, 90, 0), new Color(0.02f, 0.1f, 0.05f), new Vector2(2.2f, 0.6f));
        if (fontBold != null) st.font = fontBold;

        // 4. bugs in jars
        Vector3[] jars = { new Vector3(-5.45f, 0.93f, 8.35f), new Vector3(-3.25f, 0.93f, 8.35f), new Vector3(5.1f, 0.95f, 0.35f), new Vector3(5.1f, 0.95f, 0.95f), new Vector3(-3.6f, 0.83f, -6.2f), new Vector3(3.9f, 0.83f, -6.3f) };
        for (int i = 0; i < jars.Length; i++) BugJar(g, jars[i], i % 2 == 0);

        // 5. labelled cardboard boxes
        string[] lab = { "UNTESTED", "LEGACY CODE", "FRAGILE:\nHANDLE WITH CARE", "BUG ARCHIVE" };
        Vector3[] bp = { new Vector3(-5.0f, 0f, 5.3f), new Vector3(3.2f, 0f, 7.7f), new Vector3(-2.8f, 0f, 7.8f), new Vector3(4.9f, 0f, -3.2f) };
        float[] by = { 0f, 0f, 90f, -90f };
        for (int i = 0; i < 4; i++) LabeledBox(g, bp[i], by[i], lab[i], i);

        // 6. a big warning sign over the entrance side
        TextMeshPro sg = Text3D("Sign_NoShip", g, "NO RELEASE WITHOUT TESTING", 1.6f, new Vector3(0, 4.1f, -8.93f), new Vector3(0, 180, 0), new Color(1f, 0.7f, 0.15f), new Vector2(8f, 1f));
        if (fontBold != null) sg.font = fontBold;
    }

    static void Paper(Transform parent, Vector3 pos, float yaw, Material m)
    {
        GameObject q = Prim(PrimitiveType.Quad, "Paper", parent, pos, new Vector3(0.3f, 0.42f, 1f), m);
        q.transform.rotation = Quaternion.Euler(90, yaw, 0);
        q.GetComponent<Renderer>().shadowCastingMode = ShadowCastingMode.Off;
    }

    static void TextPaper(Transform parent, Vector3 pos, float yaw, string text)
    {
        Paper(parent, pos, yaw, matPaper2);
        TextMeshPro t = Text3D("PaperText", parent, text, 0.2f, pos + new Vector3(0, 0.002f, 0), new Vector3(90, yaw, 0), new Color(0.06f, 0.06f, 0.1f), new Vector2(0.27f, 0.38f));
        t.alignment = TextAlignmentOptions.TopLeft;
        t.margin = Vector4.zero;
    }

    static void Board(Transform parent, string name, Vector3 pos, float yaw, Vector2 size, Material m)
    {
        GameObject frame = Prim(PrimitiveType.Cube, name + "Frame", parent, pos, new Vector3(size.x + 0.1f, size.y + 0.1f, 0.05f), darkSteel);
        frame.transform.rotation = Quaternion.Euler(0, yaw, 0);
        GameObject b = Prim(PrimitiveType.Quad, name, parent, pos + new Vector3(yaw < 0 ? 0.03f : -0.03f, 0, 0), new Vector3(size.x, size.y, 1f), m);
        b.transform.rotation = Quaternion.Euler(0, yaw, 0);
    }

    static void BoardText(Transform parent, Vector3 pos, float yaw, string text, float size, Color c)
    {
        TextMeshPro t = Text3D("BoardText", parent, text, size, pos, new Vector3(0, yaw, 0), c, new Vector2(2.6f, 1.2f));
        if (fontBold != null) t.font = fontBold;
        t.alignment = TextAlignmentOptions.Center;
    }

    static void Sticky(Transform parent, Vector3 pos, float yaw, Color c, string text, float roll)
    {
        Material m = Flat("M_Sticky_" + ColorKey(c), c, 0f, 0.1f);
        GameObject q = Prim(PrimitiveType.Quad, "Sticky", parent, pos, new Vector3(0.22f, 0.22f, 1f), m);
        q.transform.rotation = Quaternion.Euler(0, yaw, roll);
        TextMeshPro t = Text3D("StickyText", parent, text, 0.55f, pos + q.transform.rotation * new Vector3(0, 0, -0.002f), q.transform.rotation.eulerAngles, new Color(0.15f, 0.1f, 0.05f), new Vector2(0.2f, 0.2f));
    }

    static void BugJar(Transform parent, Vector3 pos, bool red)
    {
        Transform j = new GameObject("BugJar").transform; j.SetParent(parent, false);
        j.position = pos;
        Prim(PrimitiveType.Cylinder, "Glass", j, new Vector3(0, 0.1f, 0), new Vector3(0.14f, 0.1f, 0.14f), matGlass);
        Prim(PrimitiveType.Cylinder, "Lid", j, new Vector3(0, 0.205f, 0), new Vector3(0.15f, 0.012f, 0.15f), black);
        Material bm = red ? matBugRed : matBugGreen;
        Prim(PrimitiveType.Sphere, "BugBody", j, new Vector3(0, 0.07f, 0), new Vector3(0.05f, 0.035f, 0.07f), bm);
        Prim(PrimitiveType.Sphere, "BugHead", j, new Vector3(0, 0.07f, -0.045f), Vector3.one * 0.028f, bm);
        for (int i = 0; i < 3; i++) foreach (int sx in new[] { -1, 1 })
        {
            GameObject leg = Prim(PrimitiveType.Capsule, "Leg", j, new Vector3(sx * 0.04f, 0.05f, -0.02f + i * 0.025f), new Vector3(0.006f, 0.03f, 0.006f), bm);
            leg.transform.localRotation = Quaternion.Euler(0, 0, sx * 55f);
        }
        Light l = NewLight("BugGlow", j, red ? new Color(1f, 0.2f, 0.1f) : new Color(0.3f, 1f, 0.4f), 0.8f, 0.9f); l.transform.localPosition = new Vector3(0, 0.12f, 0);
        Prim(PrimitiveType.Quad, "Label", j, new Vector3(0, 0.1f, -0.071f), new Vector3(0.08f, 0.05f, 1f), matPaper).transform.localRotation = Quaternion.identity;
    }

    static void LabeledBox(Transform parent, Vector3 pos, float yaw, string label, int seed)
    {
        Transform b = new GameObject("Box").transform; b.SetParent(parent, false);
        b.SetPositionAndRotation(pos, Quaternion.Euler(0, yaw, 0));
        Prim(PrimitiveType.Cube, "BoxA", b, new Vector3(0, 0.3f, 0), new Vector3(0.7f, 0.6f, 0.55f), matCardboard);
        Prim(PrimitiveType.Cube, "BoxB", b, new Vector3(0.1f, 0.77f, 0), new Vector3(0.55f, 0.34f, 0.45f), matCardboard).transform.localRotation = Quaternion.Euler(0, 12 + seed * 7, 0);
        Prim(PrimitiveType.Cube, "Tape", b, new Vector3(0, 0.6f, 0), new Vector3(0.08f, 0.004f, 0.56f), matPaper);
        TextMeshPro t = Text3D("BoxLabel", b, label, 0.7f, new Vector3(0, 0.3f, -0.278f), Vector3.zero, new Color(0.7f, 0.05f, 0.05f), new Vector2(0.6f, 0.4f));
        if (fontBold != null) t.font = fontBold;
    }

    // =====================================================================================================
    //  textures and materials for this step
    // =====================================================================================================
    static void MakeDetailAssets()
    {
        texSeams = MakeTex("WearSeams", 256, 256, new Color(0, 0, 0, 0), DrawSeams);
        texRust = MakeTex("WearRust", 256, 256, new Color(0, 0, 0, 0), DrawRust);
        texGrime = MakeTex("WearGrime", 64, 256, new Color(0, 0, 0, 0), DrawGrime);
        texPuddle = MakeTex("WearPuddle", 128, 128, new Color(0, 0, 0, 0), DrawPuddle);
        texHazard = MakeTex("WearHazard", 256, 32, new Color(0.9f, 0.7f, 0.05f, 1f), DrawHazard);
        texPaperA = MakeTex("TestPaperChecklist", 256, 340, new Color(0.86f, 0.86f, 0.82f), DrawPaperChecklist);
        texPaperB = MakeTex("TestPaperBug", 256, 340, new Color(0.86f, 0.86f, 0.82f), DrawPaperBug);
        texPaperC = MakeTex("TestPaperFlow", 256, 340, new Color(0.86f, 0.86f, 0.82f), DrawPaperFlow);
        texPaperD = MakeTex("TestPaperTable", 256, 340, new Color(0.86f, 0.86f, 0.82f), DrawPaperTable);
        texBoard = MakeTex("WhiteboardPyramid", 256, 220, new Color(0, 0, 0, 0), DrawPyramid);

        matWire = Flat("M_WireBlack", new Color(0.02f, 0.02f, 0.025f), 0f, 0.55f);
        matWireGrey = Flat("M_WireGrey", new Color(0.2f, 0.2f, 0.22f), 0f, 0.45f);
        matWireRed = Flat("M_WireRed", new Color(0.55f, 0.04f, 0.03f), 0f, 0.5f);
        matWireBlue = Flat("M_WireBlue", new Color(0.05f, 0.15f, 0.5f), 0f, 0.5f);
        matWireYellow = Flat("M_WireYellow", new Color(0.7f, 0.55f, 0.05f), 0f, 0.5f);
        matBoardWhite = Flat("M_BoardWhite", new Color(0.82f, 0.84f, 0.85f), 0f, 0.65f);
        matCork = Flat("M_Cork", new Color(0.35f, 0.24f, 0.14f), 0f, 0.1f);
        matBugRed = Emissive("M_BugRed", new Color(0.1f, 0.01f, 0.01f), new Color(1f, 0.15f, 0.08f) * 2.5f);
        matBugGreen = Emissive("M_BugGreen", new Color(0.01f, 0.08f, 0.02f), new Color(0.25f, 1f, 0.4f) * 2.5f);
        matGlass = DecalMat("M_JarGlass", Texture2D.whiteTexture, new Color(0.7f, 0.9f, 1f, 0.18f), 0.95f);
    }

    static float Noise(float x, float y, float s) { return Mathf.PerlinNoise(x * s + 17.3f, y * s + 91.7f); }

    static void DrawSeams(Texture2D t)
    {
        Color seam = new Color(0f, 0f, 0f, 0.6f), hi = new Color(0.5f, 0.5f, 0.5f, 0.14f), rivet = new Color(0.04f, 0.04f, 0.04f, 0.8f), rivHi = new Color(0.55f, 0.5f, 0.45f, 0.5f);
        Line(t, 0, 0, t.width, 0, seam, 2); Line(t, 0, 0, 0, t.height, seam, 2);
        Line(t, 3, 3, t.width, 3, hi, 1); Line(t, 3, 3, 3, t.height, hi, 1);
        foreach (int x in new[] { 18, 238 }) foreach (int y in new[] { 18, 90, 166, 238 })
        { Circle(t, x, y, 4, rivet, 2); Px(t, x - 1, y + 2, rivHi); Px(t, x, y + 2, rivHi); }
        Random.InitState(5);
        for (int i = 0; i < 26; i++)                                   // scratches
        {
            float x = Random.value * 256, y = Random.value * 256, a = Random.value * 3.14f, l = Random.Range(8, 40);
            Line(t, x, y, x + Mathf.Cos(a) * l, y + Mathf.Sin(a) * l, new Color(0.6f, 0.6f, 0.6f, Random.Range(0.05f, 0.16f)), 1);
        }
        for (int x = 0; x < 256; x++) for (int y = 0; y < 256; y++)        // dirty mottling
        {
            float n = Noise(x, y, 0.03f) * Noise(y, x, 0.07f);
            if (n > 0.3f) { Color c = t.GetPixel(x, y); float a = Mathf.Clamp01((n - 0.3f) * 0.35f); t.SetPixel(x, y, new Color(c.r * (1 - a), c.g * (1 - a), c.b * (1 - a), Mathf.Max(c.a, a * 0.8f))); }
        }
    }

    static void DrawRust(Texture2D t)
    {
        for (int x = 0; x < 256; x++) for (int y = 0; y < 256; y++)
        {
            float d = Vector2.Distance(new Vector2(x, y), new Vector2(128, 128)) / 128f;
            float n = Noise(x, y, 0.02f) * 0.6f + Noise(x, y, 0.08f) * 0.4f;
            float a = Mathf.Clamp01((n - 0.38f) * 3.2f) * Mathf.Clamp01(1.2f - d * 1.2f);
            Color c = Color.Lerp(new Color(0.28f, 0.12f, 0.05f), new Color(0.62f, 0.3f, 0.09f), Noise(x, y, 0.15f));
            t.SetPixel(x, y, new Color(c.r, c.g, c.b, a));
        }
    }

    static void DrawGrime(Texture2D t)
    {
        for (int x = 0; x < t.width; x++) for (int y = 0; y < t.height; y++)
        {
            float v = y / (float)t.height;                         // darker at the top, fades downward
            float edge = Mathf.Clamp01(Mathf.Min(x, t.width - 1 - x) / 16f);
            float n = Noise(x, y, 0.06f) * 0.6f + 0.4f * Noise(x * 4, y, 0.02f);
            float a = Mathf.Clamp01((n - 0.25f) * 1.6f) * Mathf.Pow(v, 0.8f) * edge * 0.75f;
            t.SetPixel(x, y, new Color(0.02f, 0.015f, 0.01f, a));
        }
    }

    static void DrawPuddle(Texture2D t)
    {
        for (int x = 0; x < 128; x++) for (int y = 0; y < 128; y++)
        {
            Vector2 p = new Vector2((x - 64) / 64f, (y - 64) / 64f);
            float d = p.magnitude * (1f + (Noise(x, y, 0.08f) - 0.5f) * 0.7f);
            float a = Mathf.Clamp01((1f - d) * 3f);
            float ring = Mathf.Clamp01(1f - Mathf.Abs(d - 0.92f) * 14f) * 0.25f;
            t.SetPixel(x, y, new Color(1, 1, 1, Mathf.Clamp01(a * 0.9f + ring)));
        }
    }

    static void DrawHazard(Texture2D t)
    {
        for (int x = 0; x < t.width; x++) for (int y = 0; y < t.height; y++)
        {
            bool dark = ((x + y) / 16) % 2 == 0;
            Color c = dark ? new Color(0.04f, 0.04f, 0.04f) : new Color(0.9f, 0.68f, 0.05f);
            float wear = Noise(x, y, 0.1f);
            t.SetPixel(x, y, Color.Lerp(c, new Color(0.15f, 0.12f, 0.1f), Mathf.Clamp01((wear - 0.5f) * 1.6f)));
        }
    }

    static void DrawPaperChecklist(Texture2D t)
    {
        Color ink = new Color(0.08f, 0.08f, 0.1f), gray = new Color(0.5f, 0.5f, 0.52f), green = new Color(0.1f, 0.5f, 0.2f), red = new Color(0.7f, 0.08f, 0.08f);
        Box(t, 12, 12, 232, 316, gray, false, 1);
        Box(t, 30, 290, 196, 26, ink, false, 2);
        for (int i = 0; i < 8; i++)
        {
            float y = 250 - i * 30;
            Box(t, 30, y, 16, 16, ink, false, 2);
            if (i % 3 != 2) { Line(t, 32, y + 8, 38, y + 2, green, 2); Line(t, 38, y + 2, 46, y + 18, green, 2); }
            else { Line(t, 32, y + 2, 44, y + 14, red, 2); Line(t, 32, y + 14, 44, y + 2, red, 2); }
            Line(t, 60, y + 8, 60 + 60 + (i * 41) % 110, y + 8, gray, 2);
        }
        Circle(t, 200, 40, 24, red, 3); Line(t, 180, 40, 220, 40, red, 2);
    }

    static void DrawPaperBug(Texture2D t)
    {
        Color ink = new Color(0.08f, 0.08f, 0.1f), gray = new Color(0.5f, 0.5f, 0.52f), red = new Color(0.75f, 0.08f, 0.06f);
        Box(t, 12, 12, 232, 316, gray, false, 1);
        Circle(t, 128, 250, 22, ink, 3); Circle(t, 128, 280, 9, ink, 3);                    // a bug drawing
        for (int i = -1; i <= 1; i++) { Line(t, 108, 250 + i * 12, 78, 250 + i * 20, ink, 2); Line(t, 148, 250 + i * 12, 178, 250 + i * 20, ink, 2); }
        Line(t, 122, 288, 108, 306, ink, 2); Line(t, 134, 288, 148, 306, ink, 2);
        Box(t, 30, 175, 196, 30, red, false, 3);
        for (int i = 0; i < 8; i++) Line(t, 30, 150 - i * 16, 40 + 100 + (i * 59) % 90, 150 - i * 16, gray, 2);
        Box(t, 150, 20, 76, 30, red, false, 2);
    }

    static void DrawPaperFlow(Texture2D t)
    {
        Color ink = new Color(0.08f, 0.08f, 0.1f), gray = new Color(0.5f, 0.5f, 0.52f);
        Box(t, 12, 12, 232, 316, gray, false, 1);
        Box(t, 88, 280, 80, 34, ink, false, 2);
        Line(t, 128, 280, 128, 252, ink, 2);
        Line(t, 128, 252, 168, 222, ink, 2); Line(t, 168, 222, 128, 192, ink, 2); Line(t, 128, 192, 88, 222, ink, 2); Line(t, 88, 222, 128, 252, ink, 2);
        Line(t, 168, 222, 220, 222, ink, 2); Line(t, 220, 222, 220, 150, ink, 2);
        Line(t, 128, 192, 128, 160, ink, 2);
        Box(t, 78, 126, 100, 34, ink, false, 2); Line(t, 128, 126, 128, 96, ink, 2);
        Box(t, 188, 116, 56, 34, ink, false, 2); Line(t, 220, 116, 220, 100, ink, 2); Line(t, 220, 100, 140, 100, ink, 2);
        Circle(t, 128, 70, 24, ink, 2);
        for (int i = 0; i < 3; i++) Line(t, 30, 40 - i * 10, 100, 40 - i * 10, gray, 1);
    }

    static void DrawPaperTable(Texture2D t)
    {
        Color ink = new Color(0.08f, 0.08f, 0.1f), gray = new Color(0.5f, 0.5f, 0.52f), green = new Color(0.1f, 0.5f, 0.2f), red = new Color(0.7f, 0.08f, 0.08f);
        Box(t, 12, 12, 232, 316, gray, false, 1);
        for (int r = 0; r <= 8; r++) Line(t, 24, 300 - r * 32, 232, 300 - r * 32, ink, 1);
        foreach (int x in new[] { 24, 96, 168, 232 }) Line(t, x, 300, x, 44, ink, 1);
        for (int r = 0; r < 8; r++)
        {
            bool pass = (r * 7) % 5 != 0;
            Color c = pass ? green : red;
            Line(t, 176, 280 - r * 32, 224, 280 - r * 32, c, 3);
            Line(t, 34, 284 - r * 32, 34 + 30 + (r * 23) % 40, 284 - r * 32, gray, 2);
            Line(t, 106, 284 - r * 32, 106 + 20 + (r * 31) % 40, 284 - r * 32, gray, 2);
        }
    }

    static void DrawPyramid(Texture2D t)
    {
        Color blue = new Color(0.1f, 0.25f, 0.75f, 1f), red = new Color(0.75f, 0.1f, 0.1f, 1f), green = new Color(0.1f, 0.5f, 0.2f, 1f);
        Line(t, 20, 15, 236, 15, blue, 3); Line(t, 20, 15, 128, 205, blue, 3); Line(t, 236, 15, 128, 205, blue, 3);
        Line(t, 56, 78, 200, 78, blue, 2); Line(t, 92, 140, 164, 140, blue, 2);
        Line(t, 210, 190, 235, 170, red, 3); Line(t, 235, 170, 216, 150, red, 3);                // arrow
        Circle(t, 40, 190, 14, green, 3); Line(t, 33, 190, 38, 183, green, 3); Line(t, 38, 183, 48, 198, green, 3);
    }
}
#endif
