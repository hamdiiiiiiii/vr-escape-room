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

// Menu: The Last Protocol > L5 - Floor Clutter, Rust, Mist + Light (open scene)
// Works on whichever of the two scenes is open (MainMenu or EscapeRoom_Main) and gives each its own look:
//   rust and grime on the metal, crates / barrels / debris / grates on the floor, caged wall lamps,
//   low drifting mist, and brighter lighting. Safe to run again: it replaces its own objects.
public static partial class BuildEscapeRoom
{
    static Texture2D texWood, texGrate;
    static Material matWoodCrate, matBarrel, matBarrelBand, matRustMetal, matMist, matCardboard2, matLampGlass;

    [MenuItem("The Last Protocol/L5 - Floor Clutter, Rust, Mist + Light (open scene)")]
    static void DetailPass()
    {
        string path = SceneManager.GetActiveScene().path;
        bool lab = path == LabScene, menu = path == MenuScene;
        if (!lab && !menu)
        { EditorUtility.DisplayDialog("Open a scene", "Open MainMenu or EscapeRoom_Main (Assets/Scenes 1) and run this again.", "OK"); return; }

        LoadAssets(); MakeRefAssets(); MakeDetailAssets(); MakePass2Assets();
        foreach (string n in new[] { "Pass2Details", "Pass2Lights", "Pass2Mist" })
        { Transform old = FindAny(n); if (old != null) Object.DestroyImmediate(old.gameObject); }

        Random.InitState(lab ? 303 : 707);                       // different scatter in each scene
        Transform root = new GameObject("Pass2Details").transform;

        RustPass(root, lab);
        Crates(root, lab);
        Barrels(root, lab);
        FloorGrates(root, lab);
        Debris(root, lab);
        if (lab) LabOnly(root); else MenuOnly(root);
        WallLamps(lab);
        MistPass(lab);
        LightingPass(lab);

        foreach (Collider c in root.GetComponentsInChildren<Collider>()) Object.DestroyImmediate(c);
        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        EditorSceneManager.SaveScene(SceneManager.GetActiveScene());
        Debug.Log((lab ? "Lab" : "Main menu") + ": rust, floor details, wall lamps, mist and brighter light added and scene saved. Press Play.");
    }

    // keep the floor free where the player, the menu, the dais or the furniture are
    static bool Free(float x, float z, bool lab)
    {
        if (Mathf.Abs(x) > 5.3f || Mathf.Abs(z) > 8.4f) return false;
        if (!lab)
        {
            if (Mathf.Abs(x) < 2.3f && z > -8f && z < -1.8f) return false;       // where the menu and the player stand
            if (Mathf.Abs(x) > 4.0f && z > -1.6f && z < 6.9f) return false;      // server racks
            return true;
        }
        if (new Vector2(x, z - 0.2f).magnitude < 3.3f) return false;              // dais
        if (x < -4.0f && z > -3.6f && z < 3.2f) return false;                      // kiosk and left desk
        if (x > 4.0f && z > -1.0f && z < 4.8f) return false;                       // counter
        if (z > 7.4f) return false;
        return true;
    }

    // =====================================================================================================
    //  RUST
    // =====================================================================================================
    static void RustPass(Transform parent, bool lab)
    {
        Transform g = new GameObject("Rust").transform; g.SetParent(parent, false);

        if (!lab)       // the menu has no riveted seams yet
        {
            Wall(g, "Seams_Left", new Vector3(-5.994f, 3.0f, 0f), -90f, new Vector2(18f, 6f), texSeams, 1.2f, Color.white);
            Wall(g, "Seams_Right", new Vector3(5.994f, 3.0f, 0f), 90f, new Vector2(18f, 6f), texSeams, 1.2f, Color.white);
            Wall(g, "Seams_Back", new Vector3(0f, 3.0f, 8.994f), 0f, new Vector2(12f, 6f), texSeams, 1.2f, Color.white);
            Wall(g, "Seams_Front", new Vector3(0f, 3.0f, -8.994f), 180f, new Vector2(12f, 6f), texSeams, 1.2f, Color.white);
        }

        // big orange-brown rust blooms on the walls, heavier in the menu
        int blooms = lab ? 16 : 28;
        for (int i = 0; i < blooms; i++)
        {
            int wall = Random.Range(0, 4); float along = Random.Range(-1f, 1f); Vector3 p; float yaw;
            if (wall == 0) { p = new Vector3(-5.97f, 0, along * 8f); yaw = -90f; }
            else if (wall == 1) { p = new Vector3(5.97f, 0, along * 8f); yaw = 90f; }
            else if (wall == 2) { p = new Vector3(along * 5f, 0, 8.97f); yaw = 0f; }
            else { p = new Vector3(along * 5f, 0, -8.97f); yaw = 180f; }
            p.y = Random.Range(0.4f, 3.8f);
            Decal(g, "RustBloom", p, Quaternion.Euler(0, yaw, Random.Range(-8f, 8f)), new Vector2(Random.Range(1.2f, 2.8f), Random.Range(0.9f, 2.2f)), texRust, new Color(1f, 0.85f, 0.7f, 0.95f), 0.2f);
        }
        // rust running down the wall from the pipe clamps
        for (float z = -7.5f; z <= 7.6f; z += 1.5f)
            foreach (int sx in new[] { -1, 1 })
                if (Random.value < (lab ? 0.5f : 0.75f))
                    Decal(g, "RustDrip", new Vector3(sx * 5.965f, 3.1f, z + Random.Range(-0.2f, 0.2f)), Quaternion.Euler(0, -sx * 90f, 0), new Vector2(Random.Range(0.25f, 0.5f), Random.Range(2.0f, 3.2f)), texGrime, new Color(0.7f, 0.3f, 0.1f, 0.85f), 0.1f);
        // rust stains on the floor
        for (int i = 0; i < (lab ? 14 : 22); i++)
        {
            float x = Random.Range(-5f, 5f), z = Random.Range(-8f, 8f);
            if (!Free(x, z, lab)) continue;
            Decal(g, "FloorRust", new Vector3(x, 0.005f + i * 0.00025f, z), Quaternion.Euler(90, Random.Range(0, 360), 0), Vector2.one * Random.Range(0.6f, 1.8f), texRust, new Color(0.55f, 0.28f, 0.1f, 0.6f), 0.15f);
        }
        // rust on the metal furniture
        Transform racks = FindAny("ServerRacks");
        if (racks != null)
            foreach (Transform r in racks)
            {
                if (r.name != "Rack" || Random.value < 0.25f) continue;
                int side = r.position.x < 0 ? -1 : 1;
                Decal(g, "RackRust", new Vector3(side * 4.29f, Random.Range(0.4f, 1.8f), r.position.z + Random.Range(-0.2f, 0.2f)), Quaternion.Euler(0, -side * 90f, 0), new Vector2(Random.Range(0.4f, 0.8f), Random.Range(0.5f, 1.1f)), texRust, new Color(1f, 0.85f, 0.7f, 0.9f), 0.2f);
            }
        if (lab)
        {
            foreach (Transform t in Resources.FindObjectsOfTypeAll<Transform>())
            {
                if (t == null || !t.gameObject.scene.IsValid()) continue;
                if (t.name.StartsWith("Pedestal_0"))
                {
                    Decal(g, "PedestalRust", t.TransformPoint(new Vector3(Random.Range(-0.2f, 0.2f), 0.14f, -0.406f)), t.rotation, new Vector2(0.55f, 0.3f), texRust, new Color(1f, 0.85f, 0.7f, 0.8f), 0.2f);
                    Decal(g, "PedestalGrime", t.TransformPoint(new Vector3(-0.3f, 0.6f, -0.407f)), t.rotation, new Vector2(0.25f, 0.8f), texGrime, new Color(1, 1, 1, 0.7f), 0.1f);
                }
                if (t.name == "Locker")
                    Decal(g, "LockerRust", new Vector3(t.position.x + Random.Range(-0.2f, 0.2f), Random.Range(0.3f, 1.5f), 8.19f), Quaternion.identity, new Vector2(0.5f, Random.Range(0.4f, 0.9f)), texRust, new Color(1f, 0.85f, 0.7f, 0.85f), 0.2f);
                if (t.name == "Cabinet" && t.parent != null && t.parent.name == "Counter_Right")
                    Decal(g, "CabinetRust", new Vector3(4.705f, Random.Range(0.2f, 0.7f), t.position.z + Random.Range(-0.2f, 0.2f)), Quaternion.Euler(0, 90, 0), new Vector2(0.5f, 0.4f), texRust, new Color(1f, 0.85f, 0.7f, 0.85f), 0.2f);
            }
        }
    }

    // =====================================================================================================
    //  CRATES, BARRELS, GRATES, DEBRIS
    // =====================================================================================================
    static void Crates(Transform parent, bool lab)
    {
        Transform g = new GameObject("Crates").transform; g.SetParent(parent, false);
        Vector3[] spots = lab
            ? new[] { new Vector3(-5.0f, 0, -7.7f), new Vector3(5.0f, 0, -8.0f), new Vector3(5.1f, 0, 7.1f), new Vector3(-4.2f, 0, -5.0f) }
            : new[] { new Vector3(-5.0f, 0, -7.4f), new Vector3(4.9f, 0, -6.2f), new Vector3(5.0f, 0, -7.8f), new Vector3(-5.1f, 0, -3.9f), new Vector3(-3.4f, 0, -8.1f), new Vector3(4.3f, 0, 7.6f), new Vector3(-4.7f, 0, 7.9f) };
        foreach (Vector3 s in spots)
        {
            int n = Random.Range(1, 4);
            float yaw = Random.Range(-25f, 25f);
            for (int i = 0; i < n; i++)
            {
                float size = Random.Range(0.8f, 1.05f);
                Vector3 p = s + new Vector3(i == 2 ? 0.1f : (i == 1 && n == 2 ? 0f : i * 0.95f * (Random.value < 0.5f ? -1 : 1)), 0, 0);
                int level = i == 2 ? 1 : 0;
                GameObject c = Prim(PrimitiveType.Cube, "Crate", g, new Vector3(p.x, size * 0.5f + level * 0.85f, p.z), Vector3.one * size, matWoodCrate);
                c.transform.rotation = Quaternion.Euler(0, yaw + Random.Range(-8f, 8f), 0);
                Prim(PrimitiveType.Cube, "Rim", c.transform, new Vector3(0, 0.5f, 0), new Vector3(1.03f, 0.035f, 1.03f), darkSteel).transform.localScale = new Vector3(1.03f, 0.035f, 1.03f);
                Prim(PrimitiveType.Cube, "Rim2", c.transform, new Vector3(0, -0.5f, 0), new Vector3(1.03f, 0.035f, 1.03f), darkSteel);
            }
        }
    }

    static void Barrels(Transform parent, bool lab)
    {
        Transform g = new GameObject("Barrels").transform; g.SetParent(parent, false);
        Vector3[] spots = lab
            ? new[] { new Vector3(-5.2f, 0, -6.2f), new Vector3(-5.2f, 0, -5.6f), new Vector3(5.2f, 0, 5.6f) }
            : new[] { new Vector3(5.2f, 0, -4.6f), new Vector3(5.2f, 0, -3.9f), new Vector3(4.5f, 0, -4.3f), new Vector3(-5.2f, 0, -5.8f), new Vector3(-4.4f, 0, 7.4f), new Vector3(5.1f, 0, 7.0f) };
        foreach (Vector3 s in spots)
        {
            bool tipped = Random.value < 0.18f;
            Transform b = new GameObject("Barrel").transform; b.SetParent(g, false);
            b.position = new Vector3(s.x, tipped ? 0.28f : 0f, s.z);
            if (tipped) b.rotation = Quaternion.Euler(90, Random.Range(0, 360), 0);
            Prim(PrimitiveType.Cylinder, "Body", b, new Vector3(0, 0.45f, 0), new Vector3(0.58f, 0.45f, 0.58f), matBarrel);
            foreach (float y in new[] { 0.12f, 0.45f, 0.78f })
                Prim(PrimitiveType.Cylinder, "Band", b, new Vector3(0, y, 0), new Vector3(0.6f, 0.012f, 0.6f), matBarrelBand);
            Prim(PrimitiveType.Cylinder, "Lid", b, new Vector3(0, 0.9f, 0), new Vector3(0.54f, 0.01f, 0.54f), darkSteel);
            if (Random.value < 0.5f) Prim(PrimitiveType.Cube, "Label", b, new Vector3(0, 0.55f, -0.29f), new Vector3(0.22f, 0.22f, 0.004f), amberGlow).transform.localScale = new Vector3(0.22f, 0.22f, 0.004f);
        }
    }

    // metal grating panels laid in the floor
    static void FloorGrates(Transform parent, bool lab)
    {
        Transform g = new GameObject("Grates").transform; g.SetParent(parent, false);
        int placed = 0, tries = 0;
        while (placed < (lab ? 7 : 10) && tries++ < 80)
        {
            float x = Random.Range(-4.8f, 4.8f), z = Random.Range(-7.8f, 7.8f);
            if (!Free(x, z, lab)) continue;
            float s = Random.Range(1.1f, 1.8f);
            Decal(g, "Grate", new Vector3(x, 0.009f + placed * 0.0003f, z), Quaternion.Euler(90, Random.value < 0.5f ? 0 : 90, 0), new Vector2(s, s), texGrate, new Color(1, 1, 1, 0.95f), 0.5f);
            placed++;
        }
        // yellow stencils on the floor
        if (lab) { FloorText(g, "SOFTWARE TEST AREA", new Vector3(-3.6f, 0.012f, -1.8f), 90f, 1.4f, new Color(1f, 0.3f, 0.2f)); }
        else
        {
            FloorText(g, "SECTOR 07", new Vector3(3.6f, 0.012f, 0.8f), 0f, 2.2f, new Color(1f, 0.3f, 0.2f));
            FloorText(g, "SYSTEM FAILURE", new Vector3(-3.6f, 0.012f, 3.6f), 0f, 1.4f, new Color(1f, 0.8f, 0.15f));
        }
    }

    static void Debris(Transform parent, bool lab)
    {
        Transform g = new GameObject("Debris").transform; g.SetParent(parent, false);
        int n = lab ? 46 : 62, placed = 0, tries = 0;
        string[] logs = { "ERR 0x7F\nSYSTEM FAILURE", "STACK TRACE\nnull reference\nat Vault.Open()", "LOG 03:17\nTEST RUN ABORTED", "WARNING\nUNVERIFIED BUILD" };
        while (placed < n && tries++ < 400)
        {
            float x = Random.Range(-5.1f, 5.1f), z = Random.Range(-8.2f, 8.2f);
            if (!Free(x, z, lab)) continue;
            int kind = Random.Range(0, 9);
            Vector3 p = new Vector3(x, 0, z);
            if (kind <= 1) { GameObject b = Prim(PrimitiveType.Cylinder, "Bolt", g, p + new Vector3(0, 0.012f, 0), new Vector3(0.035f, 0.012f, 0.035f), steel); b.transform.rotation = Quaternion.Euler(Random.Range(-20, 20), 0, Random.Range(-20, 20)); }
            else if (kind == 2) { GameObject c = Prim(PrimitiveType.Cylinder, "Can", g, p + new Vector3(0, 0.05f, 0), new Vector3(0.07f, 0.06f, 0.07f), matRustMetal); c.transform.rotation = Quaternion.Euler(Random.value < 0.5f ? 90 : 0, Random.Range(0, 360), 0); if (c.transform.eulerAngles.x > 45) c.transform.position = p + new Vector3(0, 0.035f, 0); }
            else if (kind == 3) { GameObject pipe = Prim(PrimitiveType.Cylinder, "PipePiece", g, p + new Vector3(0, 0.05f, 0), new Vector3(0.1f, Random.Range(0.2f, 0.5f), 0.1f), matRustMetal); pipe.transform.rotation = Quaternion.Euler(90, Random.Range(0, 360), 0); }
            else if (kind == 4) { GameObject pl = Prim(PrimitiveType.Cube, "MetalPlate", g, p + new Vector3(0, 0.012f, 0), new Vector3(Random.Range(0.25f, 0.5f), 0.02f, Random.Range(0.2f, 0.35f)), matRustMetal); pl.transform.rotation = Quaternion.Euler(Random.Range(-4f, 4f), Random.Range(0, 360), Random.Range(-4f, 4f)); }
            else if (kind == 5) { GameObject card = Prim(PrimitiveType.Quad, "Cardboard", g, p + new Vector3(0, 0.006f, 0), new Vector3(Random.Range(0.25f, 0.5f), Random.Range(0.2f, 0.4f), 1f), matCardboard2); card.transform.rotation = Quaternion.Euler(90, Random.Range(0, 360), 0); }
            else if (kind == 6) { GameObject w = Prim(PrimitiveType.Cube, "Plank", g, p + new Vector3(0, 0.02f, 0), new Vector3(Random.Range(0.5f, 1.1f), 0.03f, 0.11f), matWoodCrate); w.transform.rotation = Quaternion.Euler(0, Random.Range(0, 360), 0); }
            else if (kind == 7) { Paper(g, p + new Vector3(0, 0.008f + placed * 0.00015f, 0), Random.Range(0, 360), lab ? (Random.value < 0.5f ? matPaper : matPaper2) : matPaper2); }
            else { GameObject ball = Prim(PrimitiveType.Sphere, "Scrap", g, p + new Vector3(0, 0.04f, 0), new Vector3(Random.Range(0.06f, 0.14f), 0.06f, Random.Range(0.06f, 0.14f)), matRustMetal); ball.transform.rotation = Random.rotation; }
            placed++;
        }
        if (!lab)       // a few torn log sheets with error messages
            for (int i = 0; i < 5; i++)
            {
                float x = Random.Range(-5f, 5f), z = Random.Range(-8f, 8f);
                if (Free(x, z, lab)) TextPaper(g, new Vector3(x, 0.01f + i * 0.0008f, z), Random.Range(0, 360), logs[i % logs.Length]);
            }
    }

    // each scene gets a few things the other does not have
    static void LabOnly(Transform parent)
    {
        Transform g = new GameObject("LabExtras").transform; g.SetParent(parent, false);
        // fallen monitor with a cracked screen and a toppled chair
        Transform m = new GameObject("FallenMonitor").transform; m.SetParent(g, false);
        m.position = new Vector3(-2.9f, 0.05f, -3.0f); m.rotation = Quaternion.Euler(-80, 35, 0);
        Prim(PrimitiveType.Cube, "Panel", m, Vector3.zero, new Vector3(0.62f, 0.4f, 0.05f), black);
        Prim(PrimitiveType.Cube, "Screen", m, new Vector3(0, 0, -0.027f), new Vector3(0.56f, 0.34f, 0.005f), matKiosk);
        NewLight("FallenMonitorGlow", m, new Color(1f, 0.2f, 0.15f), 1.2f, 2f).transform.localPosition = new Vector3(0, 0, -0.4f);
        Transform ch = new GameObject("ToppledChair").transform; ch.SetParent(g, false);
        ch.position = new Vector3(3.0f, 0.25f, -2.9f); ch.rotation = Quaternion.Euler(0, 40, 85);
        Prim(PrimitiveType.Cube, "Seat", ch, Vector3.zero, new Vector3(0.5f, 0.08f, 0.5f), matChair);
        Prim(PrimitiveType.Cube, "Back", ch, new Vector3(0, 0.34f, 0.24f), new Vector3(0.46f, 0.55f, 0.06f), matChair);
        Prim(PrimitiveType.Cylinder, "Post", ch, new Vector3(0, -0.22f, 0), new Vector3(0.06f, 0.2f, 0.06f), darkSteel);
        // test-result stickers stuck to the floor guide
        FloorText(g, "FAIL", new Vector3(2.6f, 0.012f, -3.9f), 20f, 2.4f, new Color(1f, 0.15f, 0.1f));
        FloorText(g, "PASS", new Vector3(-2.7f, 0.012f, -4.6f), -15f, 2.4f, new Color(0.3f, 1f, 0.4f));
    }

    static void MenuOnly(Transform parent)
    {
        Transform g = new GameObject("MenuExtras").transform; g.SetParent(parent, false);
        // floor vents with steam, a heap of pipes, an oil drum on its side
        Vector3[] vents = { new Vector3(-3.4f, 0, 1.0f), new Vector3(3.2f, 0, 3.4f), new Vector3(-3.0f, 0, 5.4f) };
        foreach (Vector3 v in vents)
        {
            Decal(g, "VentGrate", new Vector3(v.x, 0.011f, v.z), Quaternion.Euler(90, 0, 0), new Vector2(1.2f, 1.2f), texGrate, Color.white, 0.5f);
            Steam(g, new Vector3(v.x, 0.05f, v.z));
        }
        for (int i = 0; i < 5; i++)
        {
            GameObject p = Prim(PrimitiveType.Cylinder, "Pipe", g, new Vector3(-4.9f + i * 0.04f, 0.07f + (i / 3) * 0.12f, -1.9f - (i % 3) * 0.14f), new Vector3(0.12f, 0.9f, 0.12f), matRustMetal);
            p.transform.rotation = Quaternion.Euler(90, 0, 4f * i);
        }
    }

    // =====================================================================================================
    //  WALL LAMPS (small caged lamps like in the corridor reference)
    // =====================================================================================================
    static void WallLamps(bool lab)
    {
        Transform g = new GameObject("Pass2Lights").transform;
        float[] zsL = lab ? new[] { -4.5f, 1.5f, 4.5f } : new[] { -7.5f, -1.5f, 4.5f, 7.5f };
        float[] zsR = lab ? new[] { -7.5f, -1.5f, 7.5f } : new[] { -4.5f, 1.5f, 7.5f };
        float y = lab ? 3.7f : 2.9f;
        Color c = lab ? new Color(1f, 0.78f, 0.5f) : new Color(1f, 0.58f, 0.2f);
        foreach (float z in zsL) CageLamp(g, new Vector3(-5.59f, y, z), -90f, c, lab ? 5f : 7f);
        foreach (float z in zsR) CageLamp(g, new Vector3(5.59f, y, z), 90f, c, lab ? 5f : 7f);
    }

    static void CageLamp(Transform parent, Vector3 pos, float yaw, Color c, float intensity)
    {
        Transform l = new GameObject("CageLamp").transform; l.SetParent(parent, false);
        l.SetPositionAndRotation(pos, Quaternion.Euler(0, yaw, 0));              // local -Z points into the room
        Prim(PrimitiveType.Cube, "Mount", l, new Vector3(0, 0, 0.02f), new Vector3(0.2f, 0.28f, 0.04f), black);
        Prim(PrimitiveType.Sphere, "Bulb", l, new Vector3(0, 0, -0.07f), new Vector3(0.16f, 0.2f, 0.12f), matLampGlass);
        foreach (float a in new[] { 0f, 60f, 120f })
        {
            GameObject bar = Prim(PrimitiveType.Cube, "Cage", l, new Vector3(0, 0, -0.075f), new Vector3(0.012f, 0.22f, 0.13f), black);
            bar.transform.localRotation = Quaternion.Euler(0, 0, a);
        }
        Light lt = NewLight("LampLight", l, c, intensity, 6f); lt.transform.localPosition = new Vector3(0, 0, -0.35f);
    }

    // =====================================================================================================
    //  MIST
    // =====================================================================================================
    static void MistPass(bool lab)
    {
        Transform g = new GameObject("Pass2Mist").transform;
        // a low layer along the floor and a thinner one higher up; the menu is a little thicker
        Mist(g, "MistLow", new Vector3(0, 0.45f, 0.5f), new Vector3(11f, 0.5f, 16f), lab ? 0.07f : 0.1f, lab ? new Color(0.6f, 0.72f, 0.9f) : new Color(0.75f, 0.65f, 0.55f), lab ? 30 : 42, 4f, 7f);
        Mist(g, "MistHigh", new Vector3(0, 2.2f, 0.5f), new Vector3(10f, 1.5f, 15f), lab ? 0.035f : 0.05f, lab ? new Color(0.55f, 0.7f, 1f) : new Color(0.85f, 0.7f, 0.55f), 16, 5f, 8f);
    }

    static void Mist(Transform parent, string name, Vector3 center, Vector3 size, float alpha, Color tint, int max, float s0, float s1)
    {
        GameObject go = new GameObject(name); go.transform.SetParent(parent, false); go.transform.position = center;
        ParticleSystem ps = go.AddComponent<ParticleSystem>();
        ps.Stop(true, ParticleSystemStopBehavior.StopEmittingAndClear);
        var main = ps.main;
        main.loop = true; main.prewarm = true; main.duration = 20f;
        main.startLifetime = 16f; main.startSpeed = 0.03f;
        main.startSize = new ParticleSystem.MinMaxCurve(s0, s1);
        main.startRotation = new ParticleSystem.MinMaxCurve(0f, 6.28f);
        main.startColor = new Color(tint.r, tint.g, tint.b, alpha);
        main.maxParticles = max;
        main.simulationSpace = ParticleSystemSimulationSpace.World;
        var em = ps.emission; em.rateOverTime = max / 16f;
        var sh = ps.shape; sh.enabled = true; sh.shapeType = ParticleSystemShapeType.Box; sh.scale = size;
        var col = ps.colorOverLifetime; col.enabled = true;
        Gradient grad = new Gradient();
        grad.SetKeys(new[] { new GradientColorKey(Color.white, 0f), new GradientColorKey(Color.white, 1f) },
                     new[] { new GradientAlphaKey(0f, 0f), new GradientAlphaKey(1f, 0.25f), new GradientAlphaKey(1f, 0.75f), new GradientAlphaKey(0f, 1f) });
        col.color = new ParticleSystem.MinMaxGradient(grad);
        var vel = ps.velocityOverLifetime; vel.enabled = true;
        vel.space = ParticleSystemSimulationSpace.World;
        vel.x = new ParticleSystem.MinMaxCurve(-0.08f, 0.08f); vel.y = new ParticleSystem.MinMaxCurve(-0.01f, 0.01f); vel.z = new ParticleSystem.MinMaxCurve(-0.08f, 0.08f);
        var rot = ps.rotationOverLifetime; rot.enabled = true; rot.z = new ParticleSystem.MinMaxCurve(-0.06f, 0.06f);
        ParticleSystemRenderer r = go.GetComponent<ParticleSystemRenderer>();
        r.sharedMaterial = matMist; r.renderMode = ParticleSystemRenderMode.Billboard; r.shadowCastingMode = ShadowCastingMode.Off; r.receiveShadows = false;
        ps.Play();
    }

    static void Steam(Transform parent, Vector3 pos)
    {
        GameObject go = new GameObject("Steam"); go.transform.SetParent(parent, false); go.transform.position = pos;
        go.transform.rotation = Quaternion.Euler(-90, 0, 0);
        ParticleSystem ps = go.AddComponent<ParticleSystem>();
        ps.Stop(true, ParticleSystemStopBehavior.StopEmittingAndClear);
        var main = ps.main; main.loop = true; main.prewarm = true; main.duration = 6f;
        main.startLifetime = 4f; main.startSpeed = new ParticleSystem.MinMaxCurve(0.4f, 0.8f);
        main.startSize = new ParticleSystem.MinMaxCurve(0.5f, 1.0f); main.startRotation = new ParticleSystem.MinMaxCurve(0f, 6.28f);
        main.startColor = new Color(0.85f, 0.85f, 0.9f, 0.13f); main.maxParticles = 40; main.simulationSpace = ParticleSystemSimulationSpace.World;
        var em = ps.emission; em.rateOverTime = 8f;
        var sh = ps.shape; sh.shapeType = ParticleSystemShapeType.Cone; sh.angle = 12f; sh.radius = 0.3f;
        var col = ps.colorOverLifetime; col.enabled = true;
        Gradient grad = new Gradient();
        grad.SetKeys(new[] { new GradientColorKey(Color.white, 0f), new GradientColorKey(Color.white, 1f) }, new[] { new GradientAlphaKey(0f, 0f), new GradientAlphaKey(1f, 0.2f), new GradientAlphaKey(0f, 1f) });
        col.color = new ParticleSystem.MinMaxGradient(grad);
        var sz = ps.sizeOverLifetime; sz.enabled = true; sz.size = new ParticleSystem.MinMaxCurve(1f, new AnimationCurve(new Keyframe(0, 0.5f), new Keyframe(1, 2.2f)));
        ParticleSystemRenderer r = go.GetComponent<ParticleSystemRenderer>(); r.sharedMaterial = matMist; r.shadowCastingMode = ShadowCastingMode.Off;
        ps.Play();
    }

    // =====================================================================================================
    //  LIGHTING (a little brighter; the lab stays cool, the menu stays warm)
    // =====================================================================================================
    static void LightingPass(bool lab)
    {
        RenderSettings.ambientMode = AmbientMode.Flat;
        RenderSettings.fog = true; RenderSettings.fogMode = FogMode.ExponentialSquared;
        if (lab)
        {
            RenderSettings.ambientLight = new Color(0.115f, 0.125f, 0.16f);
            RenderSettings.fogColor = new Color(0.07f, 0.08f, 0.105f);
            RenderSettings.fogDensity = 0.017f;
        }
        else
        {
            RenderSettings.ambientLight = new Color(0.085f, 0.078f, 0.08f);
            RenderSettings.fogColor = new Color(0.075f, 0.065f, 0.06f);
            RenderSettings.fogDensity = 0.021f;
        }
        Transform g = new GameObject("Pass2Lights_Fill").transform; g.SetParent(FindAny("Pass2Lights"), false);
        Color warm = new Color(1f, 0.72f, 0.45f), cool = new Color(0.55f, 0.7f, 1f);
        float[] zs = lab ? new[] { -6.5f, 1.2f, 5.5f } : new[] { -7f, -1f, 3.5f, 7f };
        foreach (float z in zs)
        {
            NewLight("SoftFill", g, lab ? cool : warm, lab ? 5f : 4.5f, 10f).transform.position = new Vector3(0, 3.0f, z);
        }
        ReflectionProbe probe = Object.FindFirstObjectByType<ReflectionProbe>();
        if (probe != null) probe.intensity = lab ? 1.5f : 1.3f;

        Volume vol = Object.FindFirstObjectByType<Volume>();
        VolumeProfile prof = vol != null ? vol.sharedProfile : null;
        if (prof != null)
        {
            if (prof.TryGet(out ColorAdjustments ca)) ca.postExposure.Override(lab ? 0.8f : 0.4f);
            if (prof.TryGet(out Vignette vg)) vg.intensity.Override(lab ? 0.28f : 0.36f);
            EditorUtility.SetDirty(prof);
        }
    }

    // =====================================================================================================
    //  textures and materials for this pass
    // =====================================================================================================
    static void MakePass2Assets()
    {
        texWood = MakeTex("CrateWood", 256, 256, new Color(0.3f, 0.2f, 0.11f), DrawWood);
        texGrate = MakeTex("FloorGrate", 128, 128, new Color(0, 0, 0, 0), DrawGrate);
        matWoodCrate = TexLit("M_WoodCrate", texWood, new Color(0.85f, 0.8f, 0.75f));
        matBarrel = Flat("M_BarrelRust", new Color(0.32f, 0.16f, 0.08f), 0.55f, 0.3f);
        matBarrelBand = Flat("M_BarrelBand", new Color(0.12f, 0.1f, 0.09f), 0.7f, 0.35f);
        matRustMetal = Flat("M_RustMetal", new Color(0.3f, 0.17f, 0.1f), 0.5f, 0.3f);
        matCardboard2 = Flat("M_CardboardScrap", new Color(0.3f, 0.21f, 0.12f), 0f, 0.2f);
        matLampGlass = Emissive("M_CageLampGlass", new Color(0.2f, 0.1f, 0.02f), new Color(1f, 0.65f, 0.25f) * 3f);
        string mp = Mats + "/M_Mist.mat";
        matMist = AssetDatabase.LoadAssetAtPath<Material>(mp);
        if (matMist == null) { matMist = new Material(Shader.Find("Universal Render Pipeline/Particles/Unlit")); AssetDatabase.CreateAsset(matMist, mp); }
        matMist.SetFloat("_Surface", 1f); matMist.SetFloat("_Blend", 0f);
        matMist.SetFloat("_SrcBlend", (float)BlendMode.SrcAlpha); matMist.SetFloat("_DstBlend", (float)BlendMode.OneMinusSrcAlpha);
        matMist.SetFloat("_SrcBlendAlpha", (float)BlendMode.One); matMist.SetFloat("_DstBlendAlpha", (float)BlendMode.OneMinusSrcAlpha);
        matMist.SetFloat("_ZWrite", 0f); matMist.SetFloat("_Cull", 0f);
        matMist.EnableKeyword("_SURFACE_TYPE_TRANSPARENT");
        matMist.SetOverrideTag("RenderType", "Transparent"); matMist.renderQueue = 3000;
        matMist.SetColor("_BaseColor", Color.white);
        matMist.SetTexture("_BaseMap", AssetDatabase.LoadAssetAtPath<Texture2D>(Gen + "/SoftDot.png"));
        EditorUtility.SetDirty(matMist);
    }

    static void DrawWood(Texture2D t)
    {
        Color dark = new Color(0.08f, 0.05f, 0.03f), nail = new Color(0.55f, 0.55f, 0.55f);
        for (int x = 0; x < 256; x++) for (int y = 0; y < 256; y++)
        {
            int plank = y / 64;
            float grain = Noise(x * 0.4f, y + plank * 31, 0.05f) * 0.6f + Noise(x, y, 0.2f) * 0.2f;
            float tint = Noise(plank * 40, 3, 0.9f);
            Color c = Color.Lerp(new Color(0.22f, 0.14f, 0.07f), new Color(0.48f, 0.34f, 0.2f), grain * 0.8f + tint * 0.3f);
            if (y % 64 < 2) c = dark;                              // gap between planks
            t.SetPixel(x, y, c);
        }
        for (int p = 0; p < 4; p++) foreach (int x in new[] { 10, 246 }) Circle(t, x, p * 64 + 32, 2, nail, 2);
        Line(t, 0, 0, 255, 255, new Color(0.15f, 0.1f, 0.06f), 8);                  // diagonal brace
        Line(t, 0, 255, 255, 0, new Color(0.15f, 0.1f, 0.06f, 0f), 1);
    }

    static void DrawGrate(Texture2D t)
    {
        Color bar = new Color(0.03f, 0.03f, 0.035f, 0.95f), edge = new Color(0.35f, 0.33f, 0.3f, 0.9f), hole = new Color(0, 0, 0, 0.5f);
        for (int x = 0; x < 128; x++) for (int y = 0; y < 128; y++)
        {
            bool cell = (x % 16 > 3) && (y % 16 > 3);
            t.SetPixel(x, y, cell ? hole : bar);
        }
        Box(t, 0, 0, 127, 127, edge, false, 4);
    }
}
#endif
