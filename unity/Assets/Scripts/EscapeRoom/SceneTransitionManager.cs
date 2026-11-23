using System;
using System.Collections;
using UnityEngine;
using UnityEngine.SceneManagement;

// Fades the screen to black and loads a scene. It never loads a scene that is not in Build Settings.
// In VR the fade is a black quad in front of the headset camera (a normal UI fade would not show in VR).
public class SceneTransitionManager : MonoBehaviour
{
    public static SceneTransitionManager Instance { get; private set; }

    public Renderer fadeQuad;
    public float fadeSeconds = 1.2f;
    public bool fadeInOnStart = true;

    Material mat;

    void Awake()
    {
        Instance = this;
        if (fadeQuad != null) { mat = fadeQuad.material; }
    }

    void Start()
    {
        if (fadeInOnStart) StartCoroutine(Fade(1f, 0f, fadeSeconds));
    }

    void OnDestroy() { if (Instance == this) Instance = null; }

    public void Load(string sceneName, Action onMissing = null)
    {
        if (string.IsNullOrEmpty(sceneName) || !Application.CanStreamedLevelBeLoaded(sceneName))
        {
            Debug.LogError("SceneTransitionManager: scene '" + sceneName + "' is not in Build Settings (File > Build Profiles > Scene List). Staying here.");
            if (onMissing != null) onMissing();
            return;
        }
        StartCoroutine(LoadRoutine(sceneName));
    }

    IEnumerator LoadRoutine(string sceneName)
    {
        yield return Fade(0f, 1f, fadeSeconds);
        AsyncOperation op = SceneManager.LoadSceneAsync(sceneName);
        while (!op.isDone) yield return null;
    }

    IEnumerator Fade(float from, float to, float seconds)
    {
        if (mat == null) yield break;
        fadeQuad.enabled = true;
        float t = 0f;
        while (t < seconds)
        {
            t += Time.deltaTime;
            Set(Mathf.Lerp(from, to, Mathf.SmoothStep(0f, 1f, t / seconds)));
            yield return null;
        }
        Set(to);
        if (to <= 0f) fadeQuad.enabled = false;
    }

    void Set(float a) { mat.SetColor("_BaseColor", new Color(0f, 0f, 0f, a)); }
}
