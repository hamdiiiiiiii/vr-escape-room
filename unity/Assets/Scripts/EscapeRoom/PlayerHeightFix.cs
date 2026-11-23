using System.Collections;
using UnityEngine;

// Safety net for eye height. Half a second after the scene starts it measures how high the camera really is.
// If it is below standing height (the simulator, or a headset that starts at the floor), it lifts the camera
// offset so the eyes are at 'eyeHeight'. A headset that already reports a normal height is left alone.
public class PlayerHeightFix : MonoBehaviour
{
    public float eyeHeight = 1.6f;
    public float lowerThreshold = 1.3f;
    public float waitSeconds = 0.6f;
    public bool Done { get; private set; }        // other scripts (the menu placement) wait for this

    IEnumerator Start()
    {
        yield return new WaitForSeconds(waitSeconds);
        Camera cam = GetComponentInChildren<Camera>();
        if (cam == null || cam.transform.parent == null) { Done = true; yield break; }
        float y = cam.transform.position.y - transform.position.y;
        if (y < lowerThreshold)
        {
            Transform offset = cam.transform.parent;               // the 'Camera Offset' object
            offset.position += Vector3.up * (eyeHeight - y);
            Debug.Log("PlayerHeightFix: eyes were at " + y.ToString("F2") + " m, lifted to " + eyeHeight.ToString("F2") + " m.");
        }
        Done = true;
    }
}
