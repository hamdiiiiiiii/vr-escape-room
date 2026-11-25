using System.Collections;
using TMPro;
using UnityEngine;
using UnityEngine.SceneManagement;

// "ENTER THE FACILITY": the vault wakes up (beacon turns amber, lights swell, lock sound),
// the wheel spins, the bolts retract, the heavy door swings open while the camera pushes in,
// the screen fades to black and the escape-room scene loads.
public class VaultSequence : MonoBehaviour
{
    [Header("Door parts")]
    public Transform doorAssembly;
    public Transform wheel;
    public Transform lockBolts;
    public GameObject behindGlow;
    public GameObject behindLight;
    public Renderer statusLamp;
    public TMP_Text statusText;

    [Header("Effects")]
    public VaultGlow glow;
    public MenuBeacon beacon;
    public MenuCameraSway cameraSway;
    public Camera cam;
    public CanvasGroup fade;                 // full-screen black overlay

    [Header("VR (set by 'The Last Protocol > 5 - Make Menu VR')")]
    public Transform rigRoot;                // the XR Origin; moved forward during the push-in
    public Renderer fadeQuad;                // black quad parented to the headset camera
    public Vector3 rigEndPosition = new Vector3(0f, 0f, 1.0f);

    [Header("Hallway (set by 'The Last Protocol > 6 - Build Hallway')")]
    public HallwayExit hallwayExit;          // when set: the door opens onto a hallway you walk down yourself
    public GameObject locomotion;            // walking / turning, switched on once the door is open

    [Header("Sound")]
    public AudioSource audioSource;
    public AudioClip grantedClip;            // short beep when the sequence starts
    public AudioClip lockClip;               // bolts / lock
    public AudioClip doorClip;               // heavy door

    [Header("Scene to load")]
    public string sceneName = "EscapeRoom_Main";

    [Header("Timing")]
    public float openAngle = 100f;
    public float spinSeconds = 2.6f;
    public float swingSeconds = 4.5f;
    public float fadeSeconds = 1.0f;
    public Vector3 cameraEndPosition = new Vector3(0f, 1.75f, 3.6f);
    public float endFov = 40f;

    // remembered start state (so a failed load can reset everything)
    Quaternion doorStart;
    Vector3[] lugStart;
    Transform[] lugs;
    Vector3 camStartPos;
    Quaternion camStartRot;
    float camStartFov;
    bool running;
    AsyncOperation preload;                  // next scene loads in the background while the door opens
    Vector3 rigStart;
    float fadeAlpha;
    MaterialPropertyBlock fadeBlock;

    void Start()
    {
        if (doorAssembly != null) doorStart = doorAssembly.localRotation;
        if (lockBolts != null)
        {
            int n = lockBolts.childCount;
            lugs = new Transform[n]; lugStart = new Vector3[n];
            for (int i = 0; i < n; i++) { lugs[i] = lockBolts.GetChild(i); lugStart[i] = lugs[i].localPosition; }
        }
        if (MenuManager.Instance != null) MenuManager.Instance.OnEnterRequested = Begin;
        else Debug.LogWarning("VaultSequence: no MenuManager found in the scene.");
    }

    public void Begin()
    {
        if (running) return;
        running = true;
        StartCoroutine(Run());
    }

    IEnumerator Run()
    {
        if (cam == null) cam = Camera.main;
        if (cameraSway != null) { camStartPos = cameraSway.BasePosition; camStartRot = cameraSway.BaseRotation; }
        if (cam != null) camStartFov = cam.fieldOfView;
        if (rigRoot != null)
        {
            rigStart = rigRoot.position;
            CharacterController cc = rigRoot.GetComponent<CharacterController>();
            if (cc != null && hallwayExit == null) cc.enabled = false;      // so the rig can be moved by script
        }

        MenuManager.Instance.HideMenu(0.5f);
        if (beacon != null) beacon.SetAmber();
        if (glow != null) glow.Activate(1.8f, 2.6f);
        SetStatus("UNLOCKING", new Color(1f, 0.6f, 0.1f));
        Play(grantedClip, 0.8f);
        yield return new WaitForSeconds(0.4f);

        Play(lockClip, 1f);
        if (hallwayExit == null) StartPreload();
        if (hallwayExit == null) StartCoroutine(PushIn(spinSeconds + swingSeconds));
        yield return Spin(spinSeconds);

        Play(doorClip, 1f);
        if (hallwayExit != null) hallwayExit.Reveal();
        SetStatus("ACCESS GRANTED", new Color(0.3f, 0.9f, 0.5f));
        if (behindGlow != null && hallwayExit == null) behindGlow.SetActive(true);
        if (behindLight != null) behindLight.SetActive(true);
        yield return Swing(swingSeconds);

        if (hallwayExit != null)
        {
            // hallway mode: the player is free. The hallway script loads the next scene when they reach its end.
            if (locomotion != null) locomotion.SetActive(true);
            hallwayExit.Arm();
            yield break;
        }

        yield return FadeTo(1f, fadeSeconds);
        yield return LoadScene();
    }

    // ---------------------------------------------------------------- steps

    IEnumerator Spin(float seconds)
    {
        float t = 0f;
        while (t < seconds)
        {
            t += Time.deltaTime;
            float k = Mathf.SmoothStep(0f, 1f, Mathf.Clamp01(t / seconds));
            if (wheel != null) wheel.localRotation = Quaternion.Euler(0f, 0f, -k * 540f);
            if (lugs != null)
                for (int i = 0; i < lugs.Length; i++)
                {
                    Vector3 dir = new Vector3(lugStart[i].x, lugStart[i].y, 0f).normalized;
                    lugs[i].localPosition = lugStart[i] - dir * (0.42f * k);
                }
            yield return null;
        }
    }

    IEnumerator Swing(float seconds)
    {
        float t = 0f;
        Light bl = behindLight != null ? behindLight.GetComponent<Light>() : null;
        float lightBase = bl != null ? bl.intensity : 0f;
        while (t < seconds)
        {
            t += Time.deltaTime;
            float k = Mathf.SmoothStep(0f, 1f, Mathf.Clamp01(t / seconds));
            if (doorAssembly != null) doorAssembly.localRotation = Quaternion.Euler(0f, k * openAngle, 0f) * doorStart;
            if (bl != null) bl.intensity = Mathf.Lerp(lightBase, lightBase * 2.5f, k);
            yield return null;
        }
    }

    // slow cinematic move toward the vault
    IEnumerator PushIn(float seconds)
    {
        if (rigRoot != null)
        {
            // VR: slide the whole XR Origin forward (no rotation, so it stays comfortable)
            float rt = 0f;
            Vector3 end = new Vector3(rigEndPosition.x, rigStart.y, rigEndPosition.z);
            while (rt < seconds)
            {
                rt += Time.deltaTime;
                float k = Mathf.SmoothStep(0f, 1f, Mathf.Clamp01(rt / seconds));
                rigRoot.position = Vector3.Lerp(rigStart, end, k);
                yield return null;
            }
            yield break;
        }
        if (cameraSway == null) yield break;
        Vector3 target = cameraEndPosition;
        float t = 0f;
        while (t < seconds)
        {
            t += Time.deltaTime;
            float k = Mathf.SmoothStep(0f, 1f, Mathf.Clamp01(t / seconds));
            Vector3 pos = Vector3.Lerp(camStartPos, target, k);
            Vector3 look = new Vector3(0f, 2.9f, 8.2f);
            cameraSway.SetBase(pos, Quaternion.LookRotation(look - pos, Vector3.up));
            if (cam != null) cam.fieldOfView = Mathf.Lerp(camStartFov, endFov, k);
            yield return null;
        }
    }

    IEnumerator FadeTo(float target, float seconds)
    {
        float start = fadeAlpha, t = 0f;
        while (t < seconds)
        {
            t += Time.unscaledDeltaTime;
            SetFade(Mathf.Lerp(start, target, Mathf.Clamp01(t / seconds)));
            if (audioSource != null && target > start) audioSource.volume = 1f - fadeAlpha;
            yield return null;
        }
        SetFade(target);
        if (audioSource != null) audioSource.volume = target > 0.5f ? 0f : 1f;
    }

    void SetFade(float a)
    {
        fadeAlpha = a;
        if (fadeQuad != null)
        {
            if (fadeBlock == null) fadeBlock = new MaterialPropertyBlock();
            fadeQuad.GetPropertyBlock(fadeBlock);
            fadeBlock.SetColor("_BaseColor", new Color(0f, 0f, 0f, a));
            fadeQuad.SetPropertyBlock(fadeBlock);
            fadeQuad.enabled = a > 0.001f;
        }
        else if (fade != null)
        {
            fade.alpha = a;
            fade.blocksRaycasts = a > 0.5f;
        }
    }

    IEnumerator LoadScene()
    {
        if (!Application.CanStreamedLevelBeLoaded(sceneName))
        {
            Debug.LogError("VaultSequence: scene '" + sceneName + "' is not in Build Settings. Use 'The Last Protocol > 4 - Create Placeholder Scene' or add your scene in File > Build Profiles.");
            SetStatus("SCENE MISSING", new Color(1f, 0.25f, 0.2f));
            MenuManager.Instance.Toast("The scene '" + sceneName + "' is not in Build Settings. See the Console for how to fix it.");
            yield return new WaitForSecondsRealtime(2.5f);
            yield return FadeTo(0f, 0.8f);
            ResetDoor();
            MenuManager.Instance.RestoreMenu();
            running = false;
            preload = null;
            yield break;
        }

        if (preload == null) StartPreload();
        preload.allowSceneActivation = true;      // already loaded, so this is almost instant
        while (!preload.isDone) yield return null;
    }

    void StartPreload()
    {
        if (preload != null || !Application.CanStreamedLevelBeLoaded(sceneName)) return;
        preload = SceneManager.LoadSceneAsync(sceneName);
        preload.allowSceneActivation = false;     // hold at 90% until the fade to black is done
    }

    void ResetDoor()
    {
        if (doorAssembly != null) doorAssembly.localRotation = doorStart;
        if (wheel != null) wheel.localRotation = Quaternion.identity;
        if (lugs != null) for (int i = 0; i < lugs.Length; i++) lugs[i].localPosition = lugStart[i];
        if (behindGlow != null) behindGlow.SetActive(false);
        if (behindLight != null) behindLight.SetActive(false);
        if (cameraSway != null) cameraSway.SetBase(camStartPos, camStartRot);
        if (cam != null && cameraSway != null) cam.fieldOfView = camStartFov;
        if (rigRoot != null)
        {
            rigRoot.position = rigStart;
            CharacterController cc = rigRoot.GetComponent<CharacterController>();
            if (cc != null) cc.enabled = true;
        }
        SetStatus("SEALED", new Color(1f, 0.25f, 0.15f));
        if (glow != null) glow.Activate(1f, 1f);
        if (audioSource != null) audioSource.volume = 1f;
    }

    void SetStatus(string text, Color c)
    {
        if (statusText != null) { statusText.text = text; statusText.color = c; }
        if (statusLamp != null)
        {
            Material m = statusLamp.material;          // an instance, the shared asset is untouched
            m.SetColor("_BaseColor", c * 0.15f);
            m.SetColor("_EmissionColor", c * 4f);
        }
    }

    void Play(AudioClip clip, float volume)
    {
        if (audioSource != null && clip != null) audioSource.PlayOneShot(clip, volume);
    }
}
