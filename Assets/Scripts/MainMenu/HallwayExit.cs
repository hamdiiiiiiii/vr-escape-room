using System.Collections;
using UnityEngine;
using UnityEngine.SceneManagement;

// Lives on the "Hallway" object behind the vault door (made by 'The Last Protocol > 6 - Build Hallway').
// When the door has opened: the glow disc is hidden, the next scene starts loading quietly in the background,
// and when the player walks to the far end of the hallway the screen fades and the next scene appears at once.
// The player walks by themselves: nothing moves the player.
public class HallwayExit : MonoBehaviour
{
    public string sceneName = "EscapeRoom_Main";
    public Transform exitPoint;              // empty object near the far end of the hallway
    public Renderer fadeQuad;                // the black quad on the headset camera
    public GameObject[] hideWhenOpen;        // black hole disc, amber haze, glow disc: hidden once the door is open
    public float triggerDistance = 1.0f;     // metres before the exit point
    public float fadeSeconds = 0f;           // 0 = no fade: the lab starts in an identical hallway, so the cut is invisible

    AsyncOperation preload;
    bool armed, leaving;
    MaterialPropertyBlock block;

    // called the moment the door starts to swing: the black disc / glow / haze behind it go away so the hallway is visible at once
    public void Reveal()
    {
        if (hideWhenOpen != null)
            foreach (GameObject g in hideWhenOpen) if (g != null) g.SetActive(false);
    }

    public void Arm()
    {
        if (armed) return;
        armed = true;
        Reveal();

        if (Application.CanStreamedLevelBeLoaded(sceneName))
        {
            preload = SceneManager.LoadSceneAsync(sceneName);
            preload.allowSceneActivation = false;      // wait at 90% until the player reaches the end
        }
        else Debug.LogError("HallwayExit: scene '" + sceneName + "' is not in Build Settings (File > Build Profiles). The hallway will not lead anywhere.");
    }

    void Update()
    {
        if (!armed || leaving || preload == null || exitPoint == null) return;
        if (preload.progress < 0.9f) return;       // lab not ready yet: keep walking, nothing happens
        Camera cam = Camera.main;
        if (cam == null) return;
        if (exitPoint.position.z - cam.transform.position.z < triggerDistance)
        {
            leaving = true;
            StartCoroutine(Leave());
        }
    }

    IEnumerator Leave()
    {
        float t = 0f;
        while (t < fadeSeconds)
        {
            t += Time.unscaledDeltaTime;
            SetFade(Mathf.Clamp01(t / fadeSeconds));
            yield return null;
        }
        if (fadeSeconds > 0f) SetFade(1f);
        while (preload.progress < 0.9f) yield return null;
        preload.allowSceneActivation = true;
        while (!preload.isDone) yield return null;
    }

    void SetFade(float a)
    {
        if (fadeQuad == null) return;
        if (block == null) block = new MaterialPropertyBlock();
        fadeQuad.GetPropertyBlock(block);
        block.SetColor("_BaseColor", new Color(0f, 0f, 0f, a));
        fadeQuad.SetPropertyBlock(block);
        fadeQuad.enabled = a > 0.001f;
    }
}
