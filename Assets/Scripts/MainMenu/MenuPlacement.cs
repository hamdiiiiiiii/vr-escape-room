using System.Collections;
using UnityEngine;

// At the start of the scene: turns the player to face the vault, then puts the menu panel
// right in front of the player's head (player -> menu -> vault).
public class MenuPlacement : MonoBehaviour
{
    public Transform rig;            // the XR Origin
    public Transform canvas;         // the menu canvas
    public Transform vault;          // what the player should face
    public float distance = 1.5f;    // metres in front of the head
    public float heightOffset = -0.1f;

    IEnumerator Start()
    {
        // the headset / simulator needs a couple of frames before its camera pose is valid
        yield return null;
        yield return null;

        // wait for the eye-height fix (if the player has one) so the panel is placed at the final head position
        PlayerHeightFix fix = rig != null ? rig.GetComponent<PlayerHeightFix>() : null;
        float guard = 0f;
        while (fix != null && !fix.Done && guard < 3f) { guard += Time.deltaTime; yield return null; }
        yield return null;

        Camera cam = Camera.main;
        if (rig == null || canvas == null || cam == null) yield break;

        if (vault != null)
        {
            Vector3 toVault = vault.position - rig.position;
            toVault.y = 0f;
            if (toVault.sqrMagnitude > 0.01f) rig.rotation = Quaternion.LookRotation(toVault.normalized, Vector3.up);
        }
        yield return null;

        Vector3 forward = rig.forward; forward.y = 0f;
        if (forward.sqrMagnitude < 0.01f) forward = Vector3.forward;
        forward.Normalize();

        Vector3 head = cam.transform.position;
        canvas.position = new Vector3(head.x, head.y + heightOffset, head.z) + forward * distance;
        canvas.rotation = Quaternion.LookRotation(forward, Vector3.up);     // text reads correctly from the player's side
    }
}
