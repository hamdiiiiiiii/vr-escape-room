#if UNITY_EDITOR
using System.Collections.Generic;
using System.IO;
using TMPro;
using UnityEditor;
using UnityEngine;

// The set dressing that recreates the reference image: round dais, cables, cracked kiosk, desks, wall monitor,
// vault lights, counter, card tester and rack, lockers, tables with papers, ceiling lights, wet floor.
public static partial class BuildEscapeRoom
{
    static Texture2D texDiagram, texKiosk, texTerminal, texPaper, softDotTex;
    static Material matDiagram, matKiosk, matTerminal, matPaper, matPaper2, matCardboard, matGrey, matGreyDark, matWood, matChair;
    static readonly Vector3 DaisCenter = new Vector3(0f, 0f, 0.2f);
    static readonly Color RedC = new Color(1f, 0.1f, 0.06f);

    // =====================================================================================================
    //  DAIS
    // =====================================================================================================
    static void BuildDais(Transform parent)
    {
        Transform d = new GameObject("Dais").transform; d.SetParent(parent, false);
        d.position = DaisCenter;
        Prim(PrimitiveType.Cylinder, "Skirt", d, new Vector3(0, 0.025f, 0), new Vector3(5.9f, 0.025f, 5.9f), black);
        Prim(PrimitiveType.Cylinder, "AmberRing", d, new Vector3(0, 0.1f, 0), new Vector3(5.4f, 0.1f, 5.4f), amberGlow);
        Prim(PrimitiveType.Cylinder, "Top", d, new Vector3(0, 0.1075f, 0), new Vector3(5.1f, 0.1075f, 5.1f), darkSteel);
        Prim(PrimitiveType.Cylinder, "Rim", d, new Vector3(0, 0.218f, 0), new Vector3(5.2f, 0.004f, 5.2f), black);
        Prim(PrimitiveType.Cylinder, "Grate1", d, new Vector3(0, 0.2165f, 0), new Vector3(4.2f, 0.002f, 4.2f), black);
        Prim(PrimitiveType.Cylinder, "Grate2", d, new Vector3(0, 0.2185f, 0), new Vector3(4.05f, 0.002f, 4.05f), darkSteel);
        Prim(PrimitiveType.Cylinder, "Grate3", d, new Vector3(0, 0.2205f, 0), new Vector3(2.9f, 0.002f, 2.9f), black);
        Prim(PrimitiveType.Cylinder, "Grate4", d, new Vector3(0, 0.2225f, 0), new Vector3(2.75f, 0.002f, 2.75f), darkSteel);
        for (int i = 0; i < 12; i++)                                         // radial grate bars
        {
            GameObject bar = Prim(PrimitiveType.Cube, "Bar", d, new Vector3(0, 0.224f, 0), new Vector3(4.15f, 0.002f, 0.025f), black);
            bar.transform.localRotation = Quaternion.Euler(0, i * 15f, 0);
        }
        for (int i = 0; i < 8; i++)                                          // bolts around the rim
        {
            float a = i * Mathf.PI / 4f;
            Prim(PrimitiveType.Cylinder, "Bolt", d, new Vector3(Mathf.Cos(a) * 2.45f, 0.222f, Mathf.Sin(a) * 2.45f), new Vector3(0.12f, 0.012f, 0.12f), steel);
        }
        NewLight("DaisAmber", d, Amber, 5f, 7f).transform.localPosition = new Vector3(0, 0.4f, -2.4f);
        foreach (Collider c in d.GetComponentsInChildren<Collider>()) Object.DestroyImmediate(c);
    }

    // =====================================================================================================
    //  EVERYTHING ELSE
    // =====================================================================================================
    static void BuildReferenceRoom(Transform parent, List<Transform> peds)
    {
        MakeRefAssets();
        Transform root = new GameObject("ReferenceDetails").transform; root.SetParent(parent, false);

        RemoveFrontRacks();
        WetFloor();
        BuildPedestalCables(root, peds);
        BuildKiosk(root, new Vector3(-5.2f, 0f, -2.3f));
        BuildLeftDesk(root, new Vector3(-5.05f, 0f, 1.0f));
        BuildBackWall(root);
        BuildCounter(root);
        BuildLockers(root);
        BuildFrontTables(root);
        BuildCeilingAndWallLights(root);
        BuildPosters(root);
        BuildFloorGlows(root);

        // only the pedestals and nothing else should block the player's rays and movement
        foreach (Collider c in root.GetComponentsInChildren<Collider>()) Object.DestroyImmediate(c);
    }

    // the menu hall had server racks all along both sides; the reference has room for desks there
    static void RemoveFrontRacks()
    {
        Transform racks = FindAny("ServerRacks");
        if (racks == null) return;
        List<GameObject> kill = new List<GameObject>();
        foreach (Transform t in racks) if (t.name == "Rack" && t.localPosition.z < 3.5f) kill.Add(t.gameObject);
        foreach (GameObject g in kill) Object.DestroyImmediate(g);
    }

    // darker, glossy, wet-looking concrete
    static void WetFloor()
    {
        Transform floor = FindAny("Floor");
        if (floor == null) return;
        Renderer rd = floor.GetComponent<Renderer>();
        Material src = rd != null ? rd.sharedMaterial : null;
        Material m = Flat("M_FloorWet", new Color(0.16f, 0.16f, 0.17f), 0.25f, 0.88f);
        if (src != null)
        {
            m.SetTexture("_BaseMap", src.GetTexture("_BaseMap"));
            m.SetTextureScale("_BaseMap", src.GetTextureScale("_BaseMap"));
            Texture n = src.GetTexture("_BumpMap");
            if (n != null) { m.SetTexture("_BumpMap", n); m.SetTextureScale("_BumpMap", src.GetTextureScale("_BaseMap")); m.SetFloat("_BumpScale", 0.6f); m.EnableKeyword("_NORMALMAP"); }
        }
        EditorUtility.SetDirty(m);
        if (rd != null) rd.sharedMaterial = m;
    }

    // =====================================================================================================
    //  cables from the pedestals across the dais and out over the floor
    // =====================================================================================================
    static void BuildPedestalCables(Transform parent, List<Transform> peds)
    {
        Transform c = new GameObject("Cables").transform; c.SetParent(parent, false);
        Vector3 j = DaisCenter + new Vector3(0, 0.235f, 0.3f);
        Prim(PrimitiveType.Cylinder, "Junction", c, j, new Vector3(0.7f, 0.025f, 0.7f), black);
        Prim(PrimitiveType.Cylinder, "JunctionRing", c, j + new Vector3(0, 0.02f, 0), new Vector3(0.5f, 0.004f, 0.5f), amberGlow);
        foreach (Transform p in peds)
        {
            Vector3 start = p.position + p.rotation * new Vector3(0.15f, 0.35f, 0.42f);
            Vector3 start2 = p.position + p.rotation * new Vector3(-0.15f, 0.25f, 0.42f);
            Vector3 mid = Vector3.Lerp(p.position, j, 0.55f);
            Bezier(c, "Cable", start, mid + new Vector3(0.15f, 0.05f, 0.3f), j, 0.05f);
            Bezier(c, "CableB", start2, mid + new Vector3(-0.3f, 0.05f, 0.5f), j + new Vector3(0, 0, 0.15f), 0.04f);
        }
        // long cables leaving the dais toward the kiosk (left), the card tester (right) and the vault
        Bezier(c, "CableLeft", j, DaisCenter + new Vector3(-2.2f, 0.05f, 1.2f), new Vector3(-5.0f, 0.06f, -1.8f), 0.06f);
        Bezier(c, "CableLeft2", j, DaisCenter + new Vector3(-3.0f, 0.04f, 0.2f), new Vector3(-4.9f, 0.05f, 0.1f), 0.045f);
        Bezier(c, "CableRight", j, DaisCenter + new Vector3(2.5f, 0.05f, 1.4f), new Vector3(4.6f, 0.06f, 1.9f), 0.06f);
        Bezier(c, "CableVault", j, DaisCenter + new Vector3(0.6f, 0.05f, 3.5f), new Vector3(0.3f, 0.1f, 6.3f), 0.09f);
    }

    // =====================================================================================================
    //  left: cracked-screen kiosk
    // =====================================================================================================
    static void BuildKiosk(Transform parent, Vector3 pos)
    {
        Transform k = new GameObject("Kiosk_Cracked").transform; k.SetParent(parent, false);
        k.SetPositionAndRotation(pos, Quaternion.LookRotation(Vector3.left));         // front (-Z) faces +X, into the room
        Prim(PrimitiveType.Cube, "Body", k, new Vector3(0, 0.95f, 0.05f), new Vector3(0.78f, 1.9f, 0.62f), steel);
        Prim(PrimitiveType.Cube, "Hood", k, new Vector3(0, 1.62f, -0.22f), new Vector3(0.84f, 0.9f, 0.3f), darkSteel);
        Prim(PrimitiveType.Cube, "ScreenFrame", k, new Vector3(0, 1.62f, -0.375f), new Vector3(0.7f, 0.76f, 0.02f), black);
        Prim(PrimitiveType.Cube, "Screen", k, new Vector3(0, 1.62f, -0.388f), new Vector3(0.62f, 0.68f, 0.006f), matKiosk);
        Prim(PrimitiveType.Cube, "Crown", k, new Vector3(0, 2.1f, 0.0f), new Vector3(0.8f, 0.08f, 0.66f), darkSteel);
        Prim(PrimitiveType.Cube, "Plate", k, new Vector3(0, 1.1f, -0.265f), new Vector3(0.66f, 0.42f, 0.03f), darkSteel);
        Prim(PrimitiveType.Cube, "Slot", k, new Vector3(0, 1.12f, -0.285f), new Vector3(0.34f, 0.1f, 0.02f), black);
        Prim(PrimitiveType.Cube, "SlotLight", k, new Vector3(0, 1.12f, -0.297f), new Vector3(0.28f, 0.016f, 0.01f), amberGlow);
        Prim(PrimitiveType.Cube, "Flap", k, new Vector3(0, 0.82f, -0.285f), new Vector3(0.4f, 0.2f, 0.03f), darkSteel);
        Prim(PrimitiveType.Cube, "Handle", k, new Vector3(0, 0.82f, -0.31f), new Vector3(0.22f, 0.025f, 0.025f), steel);
        Prim(PrimitiveType.Cube, "RedStrip", k, new Vector3(0, 0.45f, -0.32f), new Vector3(0.42f, 0.035f, 0.012f), lampRed);
        for (int i = 0; i < 6; i++) Prim(PrimitiveType.Cube, "Grille", k, new Vector3(0, 0.15f + i * 0.04f, -0.316f), new Vector3(0.5f, 0.012f, 0.006f), black);
        Prim(PrimitiveType.Cube, "Sticker", k, new Vector3(0.22f, 1.3f, -0.285f), new Vector3(0.1f, 0.1f, 0.004f), matPaper);
        Light rl = NewLight("KioskRed", k, new Color(1f, 0.15f, 0.1f), 3f, 3.5f); rl.transform.localPosition = new Vector3(0, 0.5f, -0.9f);
        Light sl = NewLight("KioskScreenGlow", k, new Color(1f, 0.25f, 0.2f), 2f, 3f); sl.transform.localPosition = new Vector3(0, 1.6f, -0.9f);
    }

    // =====================================================================================================
    //  left: desk with blue monitor, keyboard, chair
    // =====================================================================================================
    static void BuildLeftDesk(Transform parent, Vector3 pos)
    {
        Transform d = new GameObject("Desk_Left").transform; d.SetParent(parent, false);
        d.SetPositionAndRotation(pos, Quaternion.LookRotation(Vector3.left));
        Prim(PrimitiveType.Cube, "Top", d, new Vector3(0, 0.76f, 0), new Vector3(1.9f, 0.05f, 0.85f), matGreyDark);
        Prim(PrimitiveType.Cube, "DrawersL", d, new Vector3(-0.7f, 0.37f, 0.05f), new Vector3(0.45f, 0.72f, 0.7f), matGrey);
        Prim(PrimitiveType.Cube, "DrawersR", d, new Vector3(0.7f, 0.37f, 0.05f), new Vector3(0.45f, 0.72f, 0.7f), matGrey);
        for (int i = 0; i < 3; i++)
        {
            Prim(PrimitiveType.Cube, "HandleL", d, new Vector3(-0.7f, 0.12f + i * 0.22f, -0.31f), new Vector3(0.2f, 0.02f, 0.02f), black);
            Prim(PrimitiveType.Cube, "HandleR", d, new Vector3(0.7f, 0.12f + i * 0.22f, -0.31f), new Vector3(0.2f, 0.02f, 0.02f), black);
        }
        Prim(PrimitiveType.Cube, "Panel", d, new Vector3(0, 0.45f, 0.38f), new Vector3(0.95f, 0.55f, 0.03f), black);
        // monitor
        Prim(PrimitiveType.Cube, "MonitorFrame", d, new Vector3(0.1f, 1.12f, 0.15f), new Vector3(0.68f, 0.42f, 0.04f), black);
        Prim(PrimitiveType.Cube, "MonitorScreen", d, new Vector3(0.1f, 1.12f, 0.128f), new Vector3(0.62f, 0.36f, 0.005f), matDiagram);
        Prim(PrimitiveType.Cube, "MonitorNeck", d, new Vector3(0.1f, 0.9f, 0.2f), new Vector3(0.05f, 0.25f, 0.04f), darkSteel);
        Prim(PrimitiveType.Cube, "MonitorFoot", d, new Vector3(0.1f, 0.79f, 0.17f), new Vector3(0.3f, 0.015f, 0.2f), darkSteel);
        // keyboard, mouse, paper, mug
        Prim(PrimitiveType.Cube, "Keyboard", d, new Vector3(0.1f, 0.79f, -0.15f), new Vector3(0.46f, 0.015f, 0.16f), black);
        Prim(PrimitiveType.Cube, "Mouse", d, new Vector3(0.5f, 0.79f, -0.15f), new Vector3(0.06f, 0.02f, 0.1f), black);
        GameObject paper = Prim(PrimitiveType.Cube, "Paper", d, new Vector3(-0.55f, 0.79f, -0.1f), new Vector3(0.28f, 0.003f, 0.38f), matPaper);
        paper.transform.localRotation = Quaternion.Euler(0, 12, 0);
        Prim(PrimitiveType.Cylinder, "Mug", d, new Vector3(-0.2f, 0.83f, 0.2f), new Vector3(0.08f, 0.04f, 0.08f), white);
        // chair (behind the desk, toward the wall)
        Transform ch = new GameObject("Chair").transform; ch.SetParent(d, false);
        ch.localPosition = new Vector3(0.1f, 0, 0.95f); ch.localRotation = Quaternion.Euler(0, 160, 0);
        Prim(PrimitiveType.Cube, "Seat", ch, new Vector3(0, 0.48f, 0), new Vector3(0.5f, 0.08f, 0.5f), matChair);
        Prim(PrimitiveType.Cube, "Back", ch, new Vector3(0, 0.82f, 0.24f), new Vector3(0.46f, 0.55f, 0.06f), matChair);
        Prim(PrimitiveType.Cylinder, "Post", ch, new Vector3(0, 0.27f, 0), new Vector3(0.06f, 0.2f, 0.06f), darkSteel);
        for (int i = 0; i < 5; i++)
        {
            GameObject leg = Prim(PrimitiveType.Cube, "Leg", ch, new Vector3(0, 0.06f, 0), new Vector3(0.5f, 0.03f, 0.05f), darkSteel);
            leg.transform.localRotation = Quaternion.Euler(0, i * 36f, 0);
        }
        // desk lamp
        Prim(PrimitiveType.Cylinder, "LampBase", d, new Vector3(-0.85f, 0.79f, 0.25f), new Vector3(0.12f, 0.01f, 0.12f), black);
        Prim(PrimitiveType.Cylinder, "LampArm", d, new Vector3(-0.85f, 1.0f, 0.25f), new Vector3(0.015f, 0.22f, 0.015f), black);
        Prim(PrimitiveType.Sphere, "LampHead", d, new Vector3(-0.8f, 1.2f, 0.2f), new Vector3(0.12f, 0.08f, 0.12f), amberGlow);
        Light l = NewLight("LampLight", d, new Color(1f, 0.75f, 0.45f), 3f, 3.5f); l.transform.localPosition = new Vector3(-0.75f, 1.15f, 0.1f);
        Light ml = NewLight("MonitorLight", d, new Color(0.3f, 0.6f, 1f), 2.5f, 3f); ml.transform.localPosition = new Vector3(0.1f, 1.1f, -0.6f);
    }

    // =====================================================================================================
    //  back wall: wall monitor, amber light bars, desk with drawers, stool
    // =====================================================================================================
    static void BuildBackWall(Transform parent)
    {
        Transform b = new GameObject("BackWall").transform; b.SetParent(parent, false);
        // big wall monitor (left of the vault)
        Prim(PrimitiveType.Cube, "WallMonitorFrame", b, new Vector3(-4.35f, 3.1f, 8.92f), new Vector3(2.7f, 1.55f, 0.08f), black);
        Prim(PrimitiveType.Cube, "WallMonitorScreen", b, new Vector3(-4.35f, 3.1f, 8.874f), new Vector3(2.58f, 1.43f, 0.005f), matDiagram);
        Light wl = NewLight("WallMonitorGlow", b, new Color(0.3f, 0.6f, 1f), 5f, 6f); wl.transform.position = new Vector3(-4.35f, 3.0f, 7.8f);
        // amber vertical light bars on both sides of the vault
        foreach (int sx in new[] { -1, 1 })
        {
            Prim(PrimitiveType.Cube, "AmberBar", b, new Vector3(sx * 2.95f, 2.7f, 8.93f), new Vector3(0.06f, 1.0f, 0.04f), amberGlow);
            Prim(PrimitiveType.Cube, "AmberBarFrame", b, new Vector3(sx * 2.95f, 2.7f, 8.95f), new Vector3(0.12f, 1.1f, 0.03f), black);
        }
        // long desk under the monitor with drawers, a lamp, papers, a stool
        float z = 8.45f;
        Prim(PrimitiveType.Cube, "DeskTop", b, new Vector3(-4.35f, 0.9f, z), new Vector3(2.7f, 0.06f, 0.7f), matGreyDark);
        for (int i = 0; i < 3; i++)
        {
            Prim(PrimitiveType.Cube, "Drawers", b, new Vector3(-5.3f + i * 0.95f, 0.44f, z - 0.02f), new Vector3(0.85f, 0.86f, 0.62f), matGrey);
            for (int d = 0; d < 3; d++)
            {
                Prim(PrimitiveType.Cube, "DrawerLine", b, new Vector3(-5.3f + i * 0.95f, 0.2f + d * 0.28f, z - 0.335f), new Vector3(0.8f, 0.008f, 0.004f), black);
                Prim(PrimitiveType.Cube, "Handle", b, new Vector3(-5.3f + i * 0.95f, 0.3f + d * 0.28f - 0.07f, z - 0.34f), new Vector3(0.2f, 0.02f, 0.02f), black);
            }
        }
        GameObject pp = Prim(PrimitiveType.Cube, "Papers", b, new Vector3(-3.7f, 0.935f, z - 0.05f), new Vector3(0.3f, 0.02f, 0.4f), matPaper); pp.transform.localRotation = Quaternion.Euler(0, 8, 0);
        Prim(PrimitiveType.Cube, "Boxes", b, new Vector3(-3.35f, 1.0f, z + 0.05f), new Vector3(0.25f, 0.15f, 0.2f), matCardboard);
        Prim(PrimitiveType.Cylinder, "LampBase", b, new Vector3(-4.7f, 0.94f, z), new Vector3(0.14f, 0.01f, 0.14f), black);
        Prim(PrimitiveType.Cylinder, "LampArm", b, new Vector3(-4.7f, 1.2f, z), new Vector3(0.015f, 0.26f, 0.015f), black);
        Prim(PrimitiveType.Sphere, "LampHead", b, new Vector3(-4.62f, 1.46f, z - 0.05f), new Vector3(0.14f, 0.09f, 0.14f), amberGlow);
        Light l = NewLight("DeskLamp", b, new Color(1f, 0.75f, 0.45f), 4f, 4f); l.transform.position = new Vector3(-4.6f, 1.4f, z - 0.2f);
        Prim(PrimitiveType.Cylinder, "StoolSeat", b, new Vector3(-4.0f, 0.68f, z - 1.0f), new Vector3(0.36f, 0.03f, 0.36f), matChair);
        Prim(PrimitiveType.Cylinder, "StoolLeg", b, new Vector3(-4.0f, 0.33f, z - 1.0f), new Vector3(0.05f, 0.33f, 0.05f), darkSteel);
        Prim(PrimitiveType.Cylinder, "StoolBase", b, new Vector3(-4.0f, 0.03f, z - 1.0f), new Vector3(0.4f, 0.015f, 0.4f), darkSteel);
        // shelf units with boxes on the wall (right of the vault)
        for (int i = 0; i < 3; i++)
        {
            Prim(PrimitiveType.Cube, "Shelf", b, new Vector3(2.9f, 1.4f + i * 0.5f, 8.8f), new Vector3(0.0f + 1.6f, 0.03f, 0.35f), darkSteel);
            Prim(PrimitiveType.Cube, "ShelfBox", b, new Vector3(2.5f + i * 0.5f, 1.55f + i * 0.5f, 8.8f), new Vector3(0.3f, 0.22f, 0.28f), matCardboard);
        }
    }

    // =====================================================================================================
    //  right: counter, card tester, card rack
    // =====================================================================================================
    static void BuildCounter(Transform parent)
    {
        Transform c = new GameObject("Counter_Right").transform; c.SetParent(parent, false);
        float x = 5.1f;
        Prim(PrimitiveType.Cube, "Top", c, new Vector3(x, 0.92f, 1.7f), new Vector3(0.85f, 0.06f, 4.2f), matGreyDark);
        for (int i = 0; i < 5; i++)
        {
            float z = -0.1f + i * 0.85f;
            Prim(PrimitiveType.Cube, "Cabinet", c, new Vector3(x, 0.45f, z), new Vector3(0.78f, 0.88f, 0.82f), matGrey);
            Prim(PrimitiveType.Cube, "DoorLine", c, new Vector3(x - 0.395f, 0.45f, z), new Vector3(0.004f, 0.8f, 0.008f), black);
            Prim(PrimitiveType.Cube, "Handle", c, new Vector3(x - 0.41f, 0.62f, z + 0.15f), new Vector3(0.02f, 0.14f, 0.02f), black);
            Prim(PrimitiveType.Cube, "Handle2", c, new Vector3(x - 0.41f, 0.62f, z - 0.15f), new Vector3(0.02f, 0.14f, 0.02f), black);
        }
        // card tester (ATM-like): body, tilted blue screen, glowing slot lines, keypad
        Transform t = new GameObject("CardTester").transform; t.SetParent(c, false);
        t.SetPositionAndRotation(new Vector3(x + 0.05f, 0.95f, 2.2f), Quaternion.LookRotation(Vector3.right));
        Prim(PrimitiveType.Cube, "Base", t, new Vector3(0, 0.3f, 0.0f), new Vector3(0.6f, 0.6f, 0.5f), steel);
        Prim(PrimitiveType.Cube, "Head", t, new Vector3(0, 0.82f, 0.12f), new Vector3(0.62f, 0.62f, 0.25f), darkSteel);
        GameObject scr = Prim(PrimitiveType.Cube, "Screen", t, new Vector3(0, 0.84f, -0.012f), new Vector3(0.5f, 0.46f, 0.006f), matTerminal);
        Prim(PrimitiveType.Cube, "ScreenFrame", t, new Vector3(0, 0.84f, -0.006f), new Vector3(0.56f, 0.52f, 0.012f), black);
        Prim(PrimitiveType.Cube, "SlotPanel", t, new Vector3(0, 0.5f, -0.255f), new Vector3(0.5f, 0.24f, 0.02f), black);
        Prim(PrimitiveType.Cube, "BlueLineA", t, new Vector3(0, 0.56f, -0.268f), new Vector3(0.34f, 0.014f, 0.006f), screenBlue);
        Prim(PrimitiveType.Cube, "BlueLineB", t, new Vector3(0, 0.46f, -0.268f), new Vector3(0.34f, 0.014f, 0.006f), screenBlue);
        Prim(PrimitiveType.Cube, "KeypadBlock", t, new Vector3(0, 0.3f, -0.27f), new Vector3(0.36f, 0.12f, 0.04f), darkSteel);
        for (int i = 0; i < 4; i++) for (int j = 0; j < 3; j++)
            Prim(PrimitiveType.Cube, "Key", t, new Vector3(-0.08f + j * 0.08f, 0.26f + (i % 2) * 0.0f + i * 0.0f + (i - 1.5f) * 0.0f, -0.295f), new Vector3(0.05f, 0.025f, 0.01f), steel).transform.localPosition = new Vector3(-0.08f + j * 0.08f, 0.35f - i * 0.035f, -0.295f);
        Prim(PrimitiveType.Cube, "CardSlot", t, new Vector3(0.18f, 0.5f, -0.268f), new Vector3(0.1f, 0.012f, 0.006f), amberGlow);
        Light bl = NewLight("TesterBlue", t, new Color(0.3f, 0.6f, 1f), 3f, 3f); bl.transform.localPosition = new Vector3(0, 0.8f, -0.7f);
        // card rack: an A-frame with rows of cards
        Transform r = new GameObject("CardRack").transform; r.SetParent(c, false);
        r.SetPositionAndRotation(new Vector3(x + 0.0f, 0.95f, 3.35f), Quaternion.LookRotation(Vector3.right));
        Prim(PrimitiveType.Cube, "PostL", r, new Vector3(-0.3f, 0.45f, 0.0f), new Vector3(0.03f, 0.9f, 0.03f), darkSteel);
        Prim(PrimitiveType.Cube, "PostR", r, new Vector3(0.3f, 0.45f, 0.0f), new Vector3(0.03f, 0.9f, 0.03f), darkSteel);
        for (int row = 0; row < 3; row++)
        {
            GameObject shelf = Prim(PrimitiveType.Cube, "Shelf", r, new Vector3(0, 0.2f + row * 0.28f, -0.04f), new Vector3(0.63f, 0.02f, 0.14f), steel);
            for (int k = 0; k < 2; k++)
            {
                GameObject card = Prim(PrimitiveType.Cube, "Card", r, new Vector3(-0.15f + k * 0.3f, 0.34f + row * 0.28f, -0.06f), new Vector3(0.22f, 0.24f, 0.01f), matPaper2);
                card.transform.localRotation = Quaternion.Euler(-12, 0, 0);
            }
        }
        // papers, a notebook and a clipboard on the counter
        GameObject n = Prim(PrimitiveType.Cube, "Notebook", c, new Vector3(x - 0.1f, 0.96f, 0.5f), new Vector3(0.22f, 0.03f, 0.3f), matCardboard); n.transform.localRotation = Quaternion.Euler(0, 20, 0);
        GameObject p = Prim(PrimitiveType.Cube, "Papers", c, new Vector3(x - 0.05f, 0.955f, 1.1f), new Vector3(0.3f, 0.015f, 0.4f), matPaper); p.transform.localRotation = Quaternion.Euler(0, -10, 0);
        Prim(PrimitiveType.Cube, "Keyboard", c, new Vector3(x - 0.05f, 0.955f, 1.6f), new Vector3(0.18f, 0.015f, 0.45f), black);
    }

    // =====================================================================================================
    //  right-back: grey lockers and a cable drum
    // =====================================================================================================
    static void BuildLockers(Transform parent)
    {
        Transform l = new GameObject("Lockers").transform; l.SetParent(parent, false);
        for (int i = 0; i < 3; i++)
        {
            float x = 3.9f + i * 0.95f;
            Prim(PrimitiveType.Cube, "Locker", l, new Vector3(x, 1.05f, 8.5f), new Vector3(0.92f, 2.1f, 0.55f), matGrey);
            Prim(PrimitiveType.Cube, "DoorSeam", l, new Vector3(x, 1.05f, 8.2f), new Vector3(0.006f, 2.0f, 0.006f), black);
            for (int v = 0; v < 6; v++) Prim(PrimitiveType.Cube, "Vent", l, new Vector3(x, 1.85f + v * 0.04f, 8.22f), new Vector3(0.5f, 0.012f, 0.006f), black);
            Prim(PrimitiveType.Cube, "Handle", l, new Vector3(x + 0.18f, 1.05f, 8.2f), new Vector3(0.02f, 0.3f, 0.02f), black);
            Prim(PrimitiveType.Cube, "Lock", l, new Vector3(x + 0.18f, 1.25f, 8.205f), new Vector3(0.05f, 0.05f, 0.012f), steel);
        }
        // cable drum on the right wall
        Transform d = new GameObject("CableDrum").transform; d.SetParent(l, false);
        d.position = new Vector3(5.75f, 2.4f, 4.4f);
        d.rotation = Quaternion.Euler(0, 0, 90f);
        Prim(PrimitiveType.Cylinder, "Core", d, Vector3.zero, new Vector3(0.7f, 0.12f, 0.7f), rubber);
        Prim(PrimitiveType.Cylinder, "FlangeA", d, new Vector3(0, 0.14f, 0), new Vector3(1.0f, 0.015f, 1.0f), darkSteel);
        Prim(PrimitiveType.Cylinder, "FlangeB", d, new Vector3(0, -0.14f, 0), new Vector3(1.0f, 0.015f, 1.0f), darkSteel);
        d.position = new Vector3(5.7f, 2.4f, 4.4f);
        // a heavy cable hanging from it to the floor
        Bezier(l, "HangingCable", new Vector3(5.55f, 2.2f, 4.4f), new Vector3(5.4f, 0.6f, 4.2f), new Vector3(4.6f, 0.07f, 3.8f), 0.05f);
    }

    // =====================================================================================================
    //  foreground tables with papers (left and right of the player's start)
    // =====================================================================================================
    static void BuildFrontTables(Transform parent)
    {
        Transform t = new GameObject("Tables").transform; t.SetParent(parent, false);
        foreach (int sx in new[] { -1, 1 })
        {
            float x = sx * 3.7f, z = -6.4f;
            Prim(PrimitiveType.Cube, "Top", t, new Vector3(x, 0.8f, z), new Vector3(1.7f, 0.06f, 0.95f), matGreyDark);
            foreach (int lx in new[] { -1, 1 }) foreach (int lz in new[] { -1, 1 })
                Prim(PrimitiveType.Cube, "Leg", t, new Vector3(x + lx * 0.78f, 0.38f, z + lz * 0.4f), new Vector3(0.06f, 0.76f, 0.06f), darkSteel);
            Prim(PrimitiveType.Cube, "Shelf", t, new Vector3(x, 0.2f, z), new Vector3(1.6f, 0.03f, 0.85f), darkSteel);
            // loose papers with diagrams
            for (int i = 0; i < 4; i++)
            {
                GameObject paper = Prim(PrimitiveType.Cube, "Paper", t, new Vector3(x - 0.5f + i * 0.34f, 0.835f + i * 0.002f, z + (i % 2) * 0.12f - 0.05f), new Vector3(0.3f, 0.002f, 0.4f), i % 2 == 0 ? matPaper : matPaper2);
                paper.transform.localRotation = Quaternion.Euler(0, (i * 37 + (sx > 0 ? 20 : -15)) % 40 - 20, 0);
            }
            // clipboard, pens, a tablet
            GameObject cb = Prim(PrimitiveType.Cube, "Clipboard", t, new Vector3(x + 0.55f, 0.84f, z + 0.1f), new Vector3(0.26f, 0.012f, 0.36f), matWood); cb.transform.localRotation = Quaternion.Euler(0, sx * 18f, 0);
            GameObject cp = Prim(PrimitiveType.Cube, "ClipPaper", t, new Vector3(x + 0.55f, 0.848f, z + 0.1f), new Vector3(0.23f, 0.004f, 0.32f), matPaper); cp.transform.localRotation = cb.transform.localRotation;
            for (int i = 0; i < 3; i++)
            {
                GameObject pen = Prim(PrimitiveType.Cylinder, "Pen", t, new Vector3(x + 0.1f + i * 0.04f, 0.845f, z - 0.3f + i * 0.02f), new Vector3(0.012f, 0.08f, 0.012f), black);
                pen.transform.localRotation = Quaternion.Euler(90, i * 25f, 0);
            }
            Prim(PrimitiveType.Cube, "Tablet", t, new Vector3(x - 0.62f, 0.835f, z - 0.25f), new Vector3(0.3f, 0.012f, 0.2f), black);
            Prim(PrimitiveType.Cube, "TabletScreen", t, new Vector3(x - 0.62f, 0.8425f, z - 0.25f), new Vector3(0.27f, 0.002f, 0.17f), matTerminal);
        }
    }

    // =====================================================================================================
    //  lights: square ceiling panels, red strip, red wall lamp
    // =====================================================================================================
    static void BuildCeilingAndWallLights(Transform parent)
    {
        Transform g = new GameObject("RoomLights").transform; g.SetParent(parent, false);
        Material panel = Emissive("M_CeilingPanel", new Color(0.7f, 0.8f, 0.9f), new Color(0.75f, 0.88f, 1f) * 3f);
        Vector3[] ps = { new Vector3(-1.9f, 6.17f, 0.8f), new Vector3(2.4f, 6.17f, 3.2f) };
        foreach (Vector3 p in ps)
        {
            Prim(PrimitiveType.Cube, "CeilingPanel", g, p, new Vector3(0.9f, 0.04f, 0.9f), panel);
            GameObject sp = new GameObject("PanelSpot"); sp.transform.SetParent(g, false);
            sp.transform.position = p - new Vector3(0, 0.1f, 0); sp.transform.rotation = Quaternion.LookRotation(Vector3.down);
            Light l = sp.AddComponent<Light>(); l.type = LightType.Spot; l.spotAngle = 85f; l.range = 9f; l.intensity = 25f; l.color = new Color(0.7f, 0.82f, 1f); l.shadows = LightShadows.None;
        }
        Prim(PrimitiveType.Cube, "RedStrip", g, new Vector3(-5.93f, 5.6f, -3.3f), new Vector3(0.05f, 0.06f, 3.6f), lampRed);
        NewLight("RedStripLight", g, new Color(1f, 0.1f, 0.08f), 4f, 6f).transform.position = new Vector3(-5.0f, 5.3f, -3.3f);
        // red wall warning lamp
        Prim(PrimitiveType.Cube, "RedLampHousing", g, new Vector3(-5.95f, 3.6f, -0.2f), new Vector3(0.1f, 0.3f, 0.26f), black);
        Prim(PrimitiveType.Sphere, "RedLamp", g, new Vector3(-5.87f, 3.6f, -0.2f), new Vector3(0.18f, 0.18f, 0.18f), lampRed);
        NewLight("RedLampLight", g, new Color(1f, 0.12f, 0.08f), 4f, 5f).transform.position = new Vector3(-5.2f, 3.6f, -0.2f);
        // two cold work lights over the left desk
        Prim(PrimitiveType.Cube, "WorkLight", g, new Vector3(-5.93f, 2.2f, 1.2f), new Vector3(0.1f, 0.06f, 0.5f), white);
    }

    // =====================================================================================================
    //  papers pinned on the left wall
    // =====================================================================================================
    static void BuildPosters(Transform parent)
    {
        Transform g = new GameObject("Posters").transform; g.SetParent(parent, false);
        Vector2[] sizes = { new Vector2(0.42f, 0.56f), new Vector2(0.36f, 0.5f), new Vector2(0.4f, 0.52f), new Vector2(0.3f, 0.4f) };
        for (int i = 0; i < 4; i++)
        {
            GameObject q = Prim(PrimitiveType.Quad, "Poster" + i, g, new Vector3(-5.985f, 2.6f + (i % 2) * 0.15f, -3.9f + i * 0.5f), new Vector3(sizes[i].x, sizes[i].y, 1f), i % 2 == 0 ? matPaper : matPaper2);
            q.transform.rotation = Quaternion.Euler(0, -90f, (i - 1.5f) * 2.5f);
        }
    }

    // soft glow patches on the floor, standing in for reflections of the lights
    static void BuildFloorGlows(Transform parent)
    {
        Transform g = new GameObject("FloorGlows").transform; g.SetParent(parent, false);
        Glow(g, "DaisReflection", new Vector3(0f, 0.012f, -2.6f), new Vector2(5.5f, 1.4f), new Color(1f, 0.55f, 0.12f, 0.35f));
        Glow(g, "VaultReflection", new Vector3(0f, 0.012f, 5.2f), new Vector2(5.5f, 5f), new Color(1f, 0.55f, 0.12f, 0.22f));
        Glow(g, "KioskRedPatch", new Vector3(-4.3f, 0.012f, -2.3f), new Vector2(2.2f, 1.4f), new Color(1f, 0.1f, 0.08f, 0.4f));
        Glow(g, "BlueLeft", new Vector3(-3.6f, 0.012f, 1.5f), new Vector2(2.5f, 2.2f), new Color(0.25f, 0.5f, 1f, 0.22f));
        Glow(g, "BlueRight", new Vector3(3.9f, 0.012f, 2.2f), new Vector2(2.5f, 2.2f), new Color(0.25f, 0.5f, 1f, 0.25f));
        Glow(g, "PanelLeft", new Vector3(-1.9f, 0.012f, 0.8f), new Vector2(2.0f, 2.0f), new Color(0.6f, 0.75f, 1f, 0.16f));
        Glow(g, "PanelRight", new Vector3(2.4f, 0.012f, 3.2f), new Vector2(2.0f, 2.0f), new Color(0.6f, 0.75f, 1f, 0.16f));
        Glow(g, "BarsLeft", new Vector3(-2.9f, 0.012f, 7.4f), new Vector2(0.9f, 3.0f), new Color(1f, 0.55f, 0.12f, 0.25f));
        Glow(g, "BarsRight", new Vector3(2.9f, 0.012f, 7.4f), new Vector2(0.9f, 3.0f), new Color(1f, 0.55f, 0.12f, 0.25f));
    }

    static void Glow(Transform parent, string name, Vector3 pos, Vector2 size, Color c)
    {
        GameObject q = GameObject.CreatePrimitive(PrimitiveType.Quad);
        q.name = name; q.transform.SetParent(parent, false);
        q.transform.position = pos; q.transform.rotation = Quaternion.Euler(90, 0, 0);
        q.transform.localScale = new Vector3(size.x, size.y, 1f);
        Material m = AssetDatabase.LoadAssetAtPath<Material>(Mats + "/M_Glow_" + name + ".mat");
        if (m == null) { m = new Material(Shader.Find("Universal Render Pipeline/Particles/Unlit")); AssetDatabase.CreateAsset(m, Mats + "/M_Glow_" + name + ".mat"); }
        m.SetFloat("_Surface", 1f); m.SetFloat("_Blend", 2f);
        m.SetFloat("_SrcBlend", (float)UnityEngine.Rendering.BlendMode.SrcAlpha); m.SetFloat("_DstBlend", (float)UnityEngine.Rendering.BlendMode.One);
        m.SetFloat("_SrcBlendAlpha", (float)UnityEngine.Rendering.BlendMode.One); m.SetFloat("_DstBlendAlpha", (float)UnityEngine.Rendering.BlendMode.One);
        m.SetFloat("_ZWrite", 0f);
        m.EnableKeyword("_SURFACE_TYPE_TRANSPARENT");
        m.SetOverrideTag("RenderType", "Transparent");
        m.renderQueue = 3000;
        m.SetColor("_BaseColor", c);
        if (softDotTex != null) m.SetTexture("_BaseMap", softDotTex);
        EditorUtility.SetDirty(m);
        q.GetComponent<Renderer>().sharedMaterial = m;
        q.GetComponent<Renderer>().shadowCastingMode = UnityEngine.Rendering.ShadowCastingMode.Off;
        Object.DestroyImmediate(q.GetComponent<Collider>());
    }

    // =====================================================================================================
    //  generated textures + materials
    // =====================================================================================================
    static void MakeRefAssets()
    {
        softDotTex = AssetDatabase.LoadAssetAtPath<Texture2D>(Gen + "/SoftDot.png");
        texDiagram = MakeTex("RefDiagram", 512, 288, new Color(0.01f, 0.04f, 0.09f), DrawDiagram);
        texKiosk = MakeTex("RefKioskWarning", 256, 300, new Color(0.1f, 0.015f, 0.025f), DrawKiosk);
        texTerminal = MakeTex("RefTerminal", 256, 256, new Color(0.01f, 0.05f, 0.12f), DrawTerminal);
        texPaper = MakeTex("RefPaper", 256, 340, new Color(0.86f, 0.86f, 0.82f), DrawPaper);

        matDiagram = TexEmissive("M_RefDiagram", texDiagram, 1.4f);
        matKiosk = TexEmissive("M_RefKiosk", texKiosk, 1.6f);
        matTerminal = TexEmissive("M_RefTerminal", texTerminal, 1.4f);
        matPaper = TexLit("M_RefPaper", texPaper, Color.white);
        matPaper2 = TexLit("M_RefPaper2", texPaper, new Color(0.8f, 0.82f, 0.9f));
        matCardboard = Flat("M_RefCardboard", new Color(0.35f, 0.24f, 0.14f), 0f, 0.25f);
        matGrey = Flat("M_RefGrey", new Color(0.32f, 0.34f, 0.36f), 0.7f, 0.45f);
        matGreyDark = Flat("M_RefGreyDark", new Color(0.14f, 0.15f, 0.17f), 0.6f, 0.5f);
        matWood = Flat("M_RefWood", new Color(0.3f, 0.2f, 0.1f), 0f, 0.3f);
        matChair = Flat("M_RefChair", new Color(0.04f, 0.04f, 0.05f), 0f, 0.4f);
    }

    static Material TexEmissive(string name, Texture2D tex, float intensity)
    {
        Material m = Flat(name, new Color(0.02f, 0.02f, 0.03f), 0f, 0.5f);
        m.SetTexture("_EmissionMap", tex);
        m.EnableKeyword("_EMISSION");
        m.SetColor("_EmissionColor", Color.white * intensity);
        m.globalIlluminationFlags = MaterialGlobalIlluminationFlags.None;
        EditorUtility.SetDirty(m);
        return m;
    }

    static Material TexLit(string name, Texture2D tex, Color tint)
    {
        Material m = Flat(name, tint, 0f, 0.2f);
        m.SetTexture("_BaseMap", tex);
        EditorUtility.SetDirty(m);
        return m;
    }

    static Texture2D MakeTex(string name, int w, int h, Color bg, System.Action<Texture2D> draw)
    {
        string path = Gen + "/" + name + ".png";
        Texture2D t = new Texture2D(w, h, TextureFormat.RGBA32, false);
        Color[] px = new Color[w * h];
        for (int i = 0; i < px.Length; i++) px[i] = bg;
        t.SetPixels(px);
        draw(t);
        t.Apply();
        File.WriteAllBytes(path, t.EncodeToPNG());
        Object.DestroyImmediate(t);
        AssetDatabase.ImportAsset(path, ImportAssetOptions.ForceUpdate);
        return AssetDatabase.LoadAssetAtPath<Texture2D>(path);
    }

    // --- tiny drawing tools ---
    static void Px(Texture2D t, int x, int y, Color c) { if (x >= 0 && y >= 0 && x < t.width && y < t.height) t.SetPixel(x, y, c); }
    static void Line(Texture2D t, float x0, float y0, float x1, float y1, Color c, int th = 1)
    {
        int n = Mathf.CeilToInt(Mathf.Max(Mathf.Abs(x1 - x0), Mathf.Abs(y1 - y0))) + 1;
        for (int i = 0; i <= n; i++)
        {
            float k = n == 0 ? 0 : i / (float)n;
            int x = Mathf.RoundToInt(Mathf.Lerp(x0, x1, k)), y = Mathf.RoundToInt(Mathf.Lerp(y0, y1, k));
            for (int a = 0; a < th; a++) for (int b = 0; b < th; b++) Px(t, x + a, y + b, c);
        }
    }
    static void Circle(Texture2D t, float cx, float cy, float r, Color c, int th = 1)
    {
        int n = Mathf.Max(24, Mathf.CeilToInt(r * 6.3f));
        for (int i = 0; i < n; i++)
        {
            float a = i / (float)n * Mathf.PI * 2f;
            for (int q = 0; q < th; q++) Px(t, Mathf.RoundToInt(cx + Mathf.Cos(a) * r) + q, Mathf.RoundToInt(cy + Mathf.Sin(a) * r), c);
        }
    }
    static void Box(Texture2D t, float x, float y, float w, float h, Color c, bool fill = false, int th = 1)
    {
        if (fill) { for (int i = 0; i < (int)w; i++) for (int j = 0; j < (int)h; j++) Px(t, (int)x + i, (int)y + j, c); return; }
        Line(t, x, y, x + w, y, c, th); Line(t, x + w, y, x + w, y + h, c, th); Line(t, x + w, y + h, x, y + h, c, th); Line(t, x, y + h, x, y, c, th);
    }
    static void Tri(Texture2D t, float cx, float cy, float s, Color c, int th = 1)
    {
        Line(t, cx - s, cy - s * 0.8f, cx + s, cy - s * 0.8f, c, th); Line(t, cx + s, cy - s * 0.8f, cx, cy + s, c, th); Line(t, cx, cy + s, cx - s, cy - s * 0.8f, c, th);
    }

    static void DrawDiagram(Texture2D t)
    {
        Color grid = new Color(0.05f, 0.12f, 0.22f), blue = new Color(0.25f, 0.65f, 1f), dim = new Color(0.15f, 0.4f, 0.7f);
        for (int x = 0; x < t.width; x += 24) Line(t, x, 0, x, t.height, grid);
        for (int y = 0; y < t.height; y += 24) Line(t, 0, y, t.width, y, grid);
        Box(t, 6, 6, t.width - 12, t.height - 12, dim, false, 2);
        // flow chart on the left
        float[] ys = { 220, 160, 100, 45 };
        for (int i = 0; i < 4; i++) Circle(t, 50, ys[i], 14, blue, 2);
        Line(t, 64, 220, 120, 160, dim, 2); Line(t, 64, 160, 120, 160, dim, 2); Line(t, 64, 100, 120, 160, dim, 2); Line(t, 64, 45, 120, 100, dim, 2);
        Circle(t, 134, 160, 14, blue, 2); Circle(t, 134, 100, 14, blue, 2);
        Line(t, 148, 160, 200, 160, blue, 2); Line(t, 148, 100, 200, 160, blue, 2);
        Box(t, 200, 146, 28, 28, blue, false, 2); Line(t, 228, 160, 290, 160, blue, 2);
        Box(t, 290, 146, 28, 28, new Color(0.3f, 0.8f, 1f), true);
        Line(t, 318, 160, 350, 200, blue, 2); Line(t, 318, 160, 350, 110, blue, 2);
        Box(t, 350, 186, 26, 26, blue, false, 2); Box(t, 350, 96, 26, 26, blue, false, 2);
        // right panel: dial and list
        Circle(t, 440, 215, 34, blue, 2); Circle(t, 440, 215, 18, dim, 2); Line(t, 440, 175, 440, 255, dim); Line(t, 400, 215, 480, 215, dim);
        for (int i = 0; i < 6; i++) { Box(t, 405, 150 - i * 18, 14, 10, blue, i % 2 == 0, 1); Line(t, 425, 155 - i * 18, 400 + 20 + (i * 37) % 60 + 30, 155 - i * 18, dim, 2); }
        Box(t, 392, 28, 100, 140, dim, false, 1);
    }

    static void DrawKiosk(Texture2D t)
    {
        Color red = new Color(1f, 0.12f, 0.1f), dim = new Color(0.55f, 0.06f, 0.07f), crack = new Color(0.95f, 0.75f, 0.75f);
        Box(t, 8, 8, t.width - 16, t.height - 16, dim, false, 2);
        Tri(t, 128, 165, 62, red, 4);
        Line(t, 128, 175, 128, 215, red, 4); Box(t, 124, 140, 8, 8, red, true);
        Tri(t, 128, 165, 46, dim, 2);
        for (int i = 0; i < 3; i++) { float cx = 55 + i * 73; Line(t, cx, 70, cx + 14, 56, red, 2); Line(t, cx + 14, 56, cx, 42, red, 2); Line(t, cx, 42, cx - 14, 56, red, 2); Line(t, cx - 14, 56, cx, 70, red, 2); }
        // cracks radiating from the top-left corner
        Random.InitState(7);
        for (int i = 0; i < 9; i++)
        {
            float x = 40 + Random.value * 30, y = 250 + Random.value * 30, a = Random.Range(-1.9f, -0.5f);
            for (int s = 0; s < 6; s++)
            {
                float nx = x + Mathf.Cos(a) * Random.Range(18, 36), ny = y + Mathf.Sin(a) * Random.Range(18, 36);
                Line(t, x, y, nx, ny, crack, 1); x = nx; y = ny; a += Random.Range(-0.5f, 0.5f);
            }
        }
    }

    static void DrawTerminal(Texture2D t)
    {
        Color blue = new Color(0.3f, 0.7f, 1f), dim = new Color(0.12f, 0.35f, 0.65f);
        Box(t, 6, 6, t.width - 12, t.height - 12, dim, false, 2);
        Vector2[] n = { new Vector2(60, 190), new Vector2(128, 210), new Vector2(196, 190), new Vector2(90, 120), new Vector2(166, 120), new Vector2(128, 55) };
        int[,] e = { { 0, 1 }, { 1, 2 }, { 0, 3 }, { 2, 4 }, { 3, 4 }, { 3, 5 }, { 4, 5 }, { 1, 4 } };
        for (int i = 0; i < e.GetLength(0); i++) Line(t, n[e[i, 0]].x, n[e[i, 0]].y, n[e[i, 1]].x, n[e[i, 1]].y, dim, 2);
        Tri(t, n[0].x, n[0].y, 16, blue, 2); Circle(t, n[1].x, n[1].y, 15, blue, 2); Box(t, n[2].x - 14, n[2].y - 14, 28, 28, blue, false, 2);
        Circle(t, n[3].x, n[3].y, 13, blue, 2); Tri(t, n[4].x, n[4].y, 15, blue, 2); Box(t, n[5].x - 13, n[5].y - 13, 26, 26, new Color(0.3f, 0.8f, 1f), true);
    }

    static void DrawPaper(Texture2D t)
    {
        Color ink = new Color(0.08f, 0.08f, 0.1f), gray = new Color(0.45f, 0.45f, 0.48f);
        Box(t, 10, 10, t.width - 20, t.height - 20, gray, false, 1);
        for (int row = 0; row < 3; row++)
        {
            float y = 280 - row * 62;
            Circle(t, 45, y, 16, ink, 2); Box(t, 85, y - 16, 32, 32, ink, false, 2); Tri(t, 150, y, 18, ink, 2);
            Line(t, 185, y + 6, 235, y + 6, gray, 2); Line(t, 185, y - 8, 225, y - 8, gray, 2);
        }
        for (int i = 0; i < 6; i++) Line(t, 22, 78 - i * 11, 100 + (i * 53) % 130, 78 - i * 11, gray, 1);
        Box(t, 150, 22, 70, 60, ink, false, 2); Line(t, 150, 22, 220, 82, ink, 1);
    }
}
#endif
