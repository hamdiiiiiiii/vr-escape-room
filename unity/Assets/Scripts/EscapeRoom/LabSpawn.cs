using Unity.XR.CoreUtils;
using UnityEngine;

// On the XR Origin in the lab. For the first two seconds, just before every frame is drawn, it makes sure the player's
// head is at the start of the lab hallway and at standing height. The simulator (and sometimes the headset) reports its
// old position for a few frames after a scene switch; fixing it before drawing means you never see the wrong spot.
// Look for "LabSpawn:" in the Console to see if it had to move you.
public class LabSpawn : MonoBehaviour
{
    public Vector3 spawn = new Vector3(0f, 0f, -10f);
    public float tolerance = 0.5f;
    public float activeSeconds = 2f;
    public float eyeHeight = 1.6f;

    XROrigin origin;
    float startTime;
    bool liftedHeight, logged;

    void OnEnable()
    {
        origin = GetComponent<XROrigin>();
        startTime = Time.realtimeSinceStartup;
        Application.onBeforeRender += Fix;
    }

    void OnDisable() { Application.onBeforeRender -= Fix; }

    [BeforeRenderOrder(1000)]     // after the headset / simulator has updated the camera pose
    void Fix()
    {
        if (Time.realtimeSinceStartup - startTime > activeSeconds) { enabled = false; return; }
        Camera cam = Camera.main;
        if (origin == null || cam == null) return;

        Vector3 head = cam.transform.position;
        Vector2 d = new Vector2(head.x - spawn.x, head.z - spawn.z);
        if (d.magnitude > tolerance)
        {
            if (!logged) { Debug.Log("LabSpawn: head was at " + head + ", moved to the hallway start " + spawn + " (off by " + d.magnitude.ToString("F1") + " m)."); logged = true; }
            origin.MoveCameraToWorldLocation(new Vector3(spawn.x, head.y, spawn.z));
        }

        if (!liftedHeight)
        {
            float eyes = cam.transform.position.y - transform.position.y;
            if (eyes < 1.3f && cam.transform.parent != null)
                cam.transform.parent.position += Vector3.up * (eyeHeight - eyes);   // same fix as PlayerHeightFix, but immediately
            if (Time.realtimeSinceStartup - startTime > 0.1f) liftedHeight = true;
        }
    }
}
