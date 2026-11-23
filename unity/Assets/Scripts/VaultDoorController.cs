using System;
using System.Collections;
using TMPro;
using UnityEngine;

// Opens the vault door. Used by the lab (Open with a callback) and still by the old Museum_Home lobby (Open()).
// Sequence: status lamp turns green, amber ring swells, the wheel spins while the locking bolts retract,
// then the heavy door swings open and the light behind it is revealed.
public class VaultDoorController : MonoBehaviour
{
    public Transform doorAssembly;     // hinged on its left edge; rotates to open
    public Transform wheel;            // spins while opening
    public Transform lockBolts;        // children slide inwards (optional)
    public Renderer lamp;              // turns green
    public TMP_Text statusText;        // "ACCESS GRANTED" (optional)
    public GameObject lightBehind;     // glow revealed behind the door
    public GameObject glowBehind;      // optional second glow object
    public VaultGlow glow;             // amber ring pulse (optional)
    public MenuBeacon beacon;          // red beacon turns amber (optional)
    public AudioSource audioSource;
    public AudioClip lockClip, doorClip;
    public float openAngle = 100f;
    public float openTime = 3f;        // wheel time
    public float swingTime = 4.5f;

    bool opened, running;
    Quaternion doorStart = Quaternion.identity;
    Transform[] lugs; Vector3[] lugStart;
    Color lampOff;

    void Awake()
    {
        if (doorAssembly != null) doorStart = doorAssembly.localRotation;
        if (lockBolts != null)
        {
            int n = lockBolts.childCount;
            lugs = new Transform[n]; lugStart = new Vector3[n];
            for (int i = 0; i < n; i++) { lugs[i] = lockBolts.GetChild(i); lugStart[i] = lugs[i].localPosition; }
        }
        if (lightBehind != null) lightBehind.SetActive(false);
        if (glowBehind != null) glowBehind.SetActive(false);
    }

    public void Open(Action onOpened = null)
    {
        if (opened || running) return;
        opened = true;
        StartCoroutine(OpenRoutine(onOpened));
    }

    // puts everything back (used when the destination scene is missing)
    public void Close()
    {
        StopAllCoroutines();
        running = false; opened = false;
        if (doorAssembly != null) doorAssembly.localRotation = doorStart;
        if (wheel != null) wheel.localRotation = Quaternion.identity;
        if (lugs != null) for (int i = 0; i < lugs.Length; i++) lugs[i].localPosition = lugStart[i];
        if (lightBehind != null) lightBehind.SetActive(false);
        if (glowBehind != null) glowBehind.SetActive(false);
        if (statusText != null) statusText.text = "";
        SetLamp(new Color(1f, 0.15f, 0.1f));
    }

    IEnumerator OpenRoutine(Action onOpened)
    {
        running = true;
        SetLamp(new Color(0.29f, 0.87f, 0.5f));
        if (statusText != null) { statusText.text = "ACCESS GRANTED"; statusText.color = new Color(0.3f, 1f, 0.5f); }
        if (beacon != null) beacon.SetAmber();
        if (glow != null) glow.Activate(1.8f, 2.6f);
        Play(lockClip);

        float t = 0f;
        while (t < openTime)
        {
            t += Time.deltaTime;
            float k = Mathf.SmoothStep(0f, 1f, Mathf.Clamp01(t / openTime));
            if (wheel != null) wheel.localRotation = Quaternion.Euler(0f, 0f, -k * 540f);
            if (lugs != null)
                for (int i = 0; i < lugs.Length; i++)
                {
                    Vector3 dir = new Vector3(lugStart[i].x, lugStart[i].y, 0f).normalized;
                    lugs[i].localPosition = lugStart[i] - dir * (0.42f * k);
                }
            yield return null;
        }

        Play(doorClip);
        if (lightBehind != null) lightBehind.SetActive(true);
        if (glowBehind != null) glowBehind.SetActive(true);
        Light bl = lightBehind != null ? lightBehind.GetComponent<Light>() : null;
        float lightBase = bl != null ? bl.intensity : 0f;
        t = 0f;
        while (t < swingTime)
        {
            t += Time.deltaTime;
            float k = Mathf.SmoothStep(0f, 1f, Mathf.Clamp01(t / swingTime));
            if (doorAssembly != null) doorAssembly.localRotation = Quaternion.Euler(0f, k * openAngle, 0f) * doorStart;
            if (bl != null) bl.intensity = Mathf.Lerp(lightBase, lightBase * 2.5f, k);
            yield return null;
        }

        yield return new WaitForSeconds(1.0f);
        running = false;
        if (onOpened != null) onOpened();
    }

    void SetLamp(Color c)
    {
        if (lamp == null) return;
        PulseGlow pulse = lamp.GetComponent<PulseGlow>();
        if (pulse != null) pulse.enabled = false;
        lamp.SetPropertyBlock(null);
        lamp.material.SetColor("_BaseColor", c * 0.2f);
        lamp.material.SetColor("_EmissionColor", c * 4f);
    }

    void Play(AudioClip c) { if (audioSource != null && c != null) audioSource.PlayOneShot(c, 1f); }
}
