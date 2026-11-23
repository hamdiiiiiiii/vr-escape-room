using System.Collections;
using TMPro;
using UnityEngine;
using UnityEngine.InputSystem;
using UnityEngine.UI;

// Controls the main menu: button clicks, smooth panel transitions, sounds and the Exit button.
// The vault opening sequence (Enter the Facility) is added in a later stage through OnEnterRequested.
public class MenuManager : MonoBehaviour
{
    public static MenuManager Instance { get; private set; }

    [Header("Panels")]
    public CanvasGroup mainGroup;
    public CanvasGroup howToGroup;
    public CanvasGroup settingsGroup;

    [Header("Main buttons")]
    public Button enterButton;
    public Button howToButton;
    public Button settingsButton;
    public Button exitButton;

    [Header("Panel back buttons")]
    public Button howToBack;
    public Button settingsBack;

    [Header("Feedback")]
    public TMP_Text toastText;

    [Header("Sound")]
    public AudioSource audioSource;
    public AudioClip hoverClip;
    public AudioClip clickClip;

    // Set by the vault sequence script (next stage). While it is null, Enter shows a short message.
    public System.Action OnEnterRequested;

    CanvasGroup current;
    bool switching;
    bool locked;                 // true once the player has entered the facility
    Coroutine toastRoutine;

    void Awake()
    {
        Instance = this;
        Time.timeScale = 1f;

        Hook(enterButton, OnEnter);
        Hook(howToButton, () => Show(howToGroup));
        Hook(settingsButton, () => Show(settingsGroup));
        Hook(exitButton, OnExit);
        Hook(howToBack, () => Show(mainGroup));
        Hook(settingsBack, () => Show(mainGroup));

        SetGroup(mainGroup, true, 1f);
        SetGroup(howToGroup, false, 0f);
        SetGroup(settingsGroup, false, 0f);
        current = mainGroup;
        if (toastText != null) toastText.alpha = 0f;
    }

    void Hook(Button b, UnityEngine.Events.UnityAction action)
    {
        if (b == null) return;
        b.onClick.AddListener(() => { PlayClick(); action(); });
    }

    void Update()
    {
        if (locked) return;
        Keyboard kb = Keyboard.current;
        if (kb != null && kb.escapeKey.wasPressedThisFrame && current != mainGroup) Show(mainGroup);
    }

    // ---------------------------------------------------------------- buttons

    void OnEnter()
    {
        if (locked) return;
        if (OnEnterRequested != null) { locked = true; OnEnterRequested(); }
        else Toast("ENTER THE FACILITY: the vault sequence is added in the next stage.");
    }

    void OnExit()
    {
#if UNITY_EDITOR
        // Never stop Play mode or close the editor from this button.
        Debug.Log("Exit pressed: in a built game this closes the application.");
        Toast("EXIT GAME works in a build. (Ignored inside the Unity Editor.)");
#else
        Application.Quit();
#endif
    }

    // ---------------------------------------------------------------- panels

    public void Show(CanvasGroup target)
    {
        if (switching || target == null || target == current) return;
        StartCoroutine(Switch(current, target));
    }

    IEnumerator Switch(CanvasGroup from, CanvasGroup to)
    {
        switching = true;
        yield return Fade(from, 1f, 0f, 0.18f);
        from.gameObject.SetActive(false);
        to.gameObject.SetActive(true);
        yield return Fade(to, 0f, 1f, 0.28f);
        SetGroup(to, true, 1f);
        current = to;
        switching = false;
    }

    IEnumerator Fade(CanvasGroup g, float a, float b, float seconds)
    {
        g.interactable = false;
        g.blocksRaycasts = false;
        RectTransform rt = (RectTransform)g.transform;
        float t = 0f;
        while (t < seconds)
        {
            t += Time.unscaledDeltaTime;
            float k = Mathf.SmoothStep(0f, 1f, Mathf.Clamp01(t / seconds));
            g.alpha = Mathf.Lerp(a, b, k);
            rt.localScale = Vector3.one * Mathf.Lerp(0.97f, 1f, g.alpha);
            yield return null;
        }
        g.alpha = b;
        rt.localScale = Vector3.one;
    }

    void SetGroup(CanvasGroup g, bool on, float alpha)
    {
        if (g == null) return;
        g.gameObject.SetActive(on || alpha > 0f);
        g.alpha = alpha;
        g.interactable = on;
        g.blocksRaycasts = on;
        if (!on && alpha <= 0f) g.gameObject.SetActive(false);
    }

    // Hides the whole main group (used when the vault sequence starts).
    public void HideMenu(float seconds = 0.6f)
    {
        StartCoroutine(HideRoutine(seconds));
    }

    IEnumerator HideRoutine(float seconds)
    {
        yield return Fade(current, current.alpha, 0f, seconds);
        current.gameObject.SetActive(false);
    }

    // Brings the main menu back (used if the next scene cannot be loaded).
    public void RestoreMenu()
    {
        locked = false;
        mainGroup.gameObject.SetActive(true);
        SetGroup(mainGroup, true, 1f);
        ((RectTransform)mainGroup.transform).localScale = Vector3.one;
        current = mainGroup;
    }

    // ---------------------------------------------------------------- feedback

    public void Toast(string message)
    {
        if (toastText == null) { Debug.Log(message); return; }
        if (toastRoutine != null) StopCoroutine(toastRoutine);
        toastRoutine = StartCoroutine(ToastRoutine(message));
    }

    IEnumerator ToastRoutine(string message)
    {
        toastText.text = message;
        float t = 0f;
        while (t < 0.2f) { t += Time.unscaledDeltaTime; toastText.alpha = t / 0.2f; yield return null; }
        toastText.alpha = 1f;
        yield return new WaitForSecondsRealtime(3f);
        t = 0f;
        while (t < 0.5f) { t += Time.unscaledDeltaTime; toastText.alpha = 1f - t / 0.5f; yield return null; }
        toastText.alpha = 0f;
    }

    public void PlayHover() { if (audioSource != null && hoverClip != null) audioSource.PlayOneShot(hoverClip, 0.35f); }
    public void PlayClick() { if (audioSource != null && clickClip != null) audioSource.PlayOneShot(clickClip, 0.9f); }
}
