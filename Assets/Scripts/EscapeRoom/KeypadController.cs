using System;
using System.Collections;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

// The keypad on one pedestal: six digits, display, ENTER, CLEAR, green / red lamps, sounds and button animation.
// It knows nothing about codes. It only reports the typed code through Submitted.
public class KeypadController : MonoBehaviour
{
    public TMP_Text roomLabel;
    public TMP_Text display;
    public Button[] digitButtons = new Button[10];
    public Button enterButton, clearButton, closeButton, openButton;
    public GameObject idleOverlay;                 // "PRESS E" cover, shown while the keypad is closed
    public CanvasGroup keys;                       // all keys; not clickable while closed
    public Renderer greenLamp, redLamp;
    public Light greenLight, redLight;
    public AudioSource audioSource;
    public AudioClip keyClip, clearClip, grantedClip, deniedClip;

    public event Action<string> Submitted;
    public event Action CloseRequested;
    public event Action OpenRequested;

    readonly System.Text.StringBuilder buffer = new System.Text.StringBuilder();
    bool open, busy;
    Color greenOn, redOn;
    Material greenMat, redMat;
    Coroutine messageRoutine, blinkRoutine;

    void Awake()
    {
        for (int i = 0; i < digitButtons.Length; i++)
        {
            int d = i;
            if (digitButtons[i] != null) digitButtons[i].onClick.AddListener(() => PressDigit(d));
        }
        if (enterButton != null) enterButton.onClick.AddListener(PressEnter);
        if (clearButton != null) clearButton.onClick.AddListener(PressClear);
        if (closeButton != null) closeButton.onClick.AddListener(() => { if (CloseRequested != null) CloseRequested(); });
        if (openButton != null) openButton.onClick.AddListener(() => { if (OpenRequested != null) OpenRequested(); });

        if (greenLamp != null) { greenMat = greenLamp.material; greenOn = greenMat.GetColor("_EmissionColor"); }
        if (redLamp != null) { redMat = redLamp.material; redOn = redMat.GetColor("_EmissionColor"); }
        SetLamps(false, false);
        SetOpen(false);
    }

    public void SetRoomName(string n) { if (roomLabel != null) roomLabel.text = n; }

    public void SetOpen(bool value)
    {
        open = value;
        if (idleOverlay != null) idleOverlay.SetActive(!value);
        if (keys != null) { keys.interactable = value; keys.blocksRaycasts = value; }
        if (value && !busy) { buffer.Length = 0; Refresh(); }
        if (!value) { StopMessage(); SetLamps(false, false); if (!busy) { buffer.Length = 0; Refresh(); } }
    }

    // ----------------------------------------------------------- keys

    public void PressDigit(int d)
    {
        if (!open || busy) return;
        if (buffer.Length >= AccessCodeManager.CodeLength) { Play(clearClip); return; }   // never more than six
        buffer.Append((char)('0' + d));
        Play(keyClip);
        if (digitButtons[d] != null) StartCoroutine(Punch(digitButtons[d].transform));
        Refresh();
    }

    public void PressClear()
    {
        if (!open || busy) return;
        buffer.Length = 0;
        SetLamps(false, false);
        Play(clearClip);
        if (clearButton != null) StartCoroutine(Punch(clearButton.transform));
        Refresh();
    }

    public void PressEnter()
    {
        if (!open || busy) return;
        if (enterButton != null) StartCoroutine(Punch(enterButton.transform));
        if (buffer.Length != AccessCodeManager.CodeLength)            // never fewer than six
        {
            Play(deniedClip);
            ShowMessage("ENTER 6 DIGITS", new Color(1f, 0.7f, 0.2f), 1.2f);
            return;
        }
        Play(keyClip);
        if (Submitted != null) Submitted(buffer.ToString());
    }

    // ----------------------------------------------------------- results

    public void ShowGranted()
    {
        busy = true;
        StopMessage();
        SetLamps(true, false);
        Play(grantedClip);
        SetDisplay("ACCESS GRANTED", new Color(0.3f, 1f, 0.5f));
    }

    public void ShowDenied()
    {
        busy = true;
        StopMessage();
        Play(deniedClip);
        SetDisplay("ACCESS DENIED", new Color(1f, 0.25f, 0.2f));
        blinkRoutine = StartCoroutine(DeniedRoutine());
    }

    public void ShowMessage(string text, Color c, float seconds)
    {
        StopMessage();
        messageRoutine = StartCoroutine(MessageRoutine(text, c, seconds));
    }

    // used when the sequence is finished or failed, so the player can try again
    public void Unlock() { busy = false; buffer.Length = 0; SetLamps(false, false); Refresh(); }

    IEnumerator DeniedRoutine()
    {
        for (int i = 0; i < 3; i++)
        {
            SetLamps(false, true); yield return new WaitForSeconds(0.25f);
            SetLamps(false, false); yield return new WaitForSeconds(0.15f);
        }
        SetLamps(false, true);
        yield return new WaitForSeconds(0.5f);
        SetLamps(false, false);
        buffer.Length = 0;
        busy = false;
        Refresh();
    }

    IEnumerator MessageRoutine(string text, Color c, float seconds)
    {
        SetDisplay(text, c);
        yield return new WaitForSeconds(seconds);
        messageRoutine = null;
        Refresh();
    }

    void StopMessage()
    {
        if (messageRoutine != null) { StopCoroutine(messageRoutine); messageRoutine = null; }
        if (blinkRoutine != null) { StopCoroutine(blinkRoutine); blinkRoutine = null; }
    }

    // ----------------------------------------------------------- helpers

    void Refresh()
    {
        if (display == null) return;
        string s = "";
        for (int i = 0; i < AccessCodeManager.CodeLength; i++)
            s += (i < buffer.Length ? buffer[i].ToString() : "_") + (i < AccessCodeManager.CodeLength - 1 ? " " : "");
        SetDisplay(s, new Color(0.35f, 0.95f, 1f));
    }

    void SetDisplay(string t, Color c) { if (display != null) { display.text = t; display.color = c; } }

    void SetLamps(bool green, bool red)
    {
        if (greenMat != null) greenMat.SetColor("_EmissionColor", green ? greenOn : Color.black);
        if (redMat != null) redMat.SetColor("_EmissionColor", red ? redOn : Color.black);
        if (greenLight != null) greenLight.enabled = green;
        if (redLight != null) redLight.enabled = red;
    }

    void Play(AudioClip c) { if (audioSource != null && c != null) audioSource.PlayOneShot(c); }

    // the button visibly sinks and springs back
    IEnumerator Punch(Transform t)
    {
        Vector3 start = Vector3.one;
        float time = 0f;
        while (time < 0.12f)
        {
            time += Time.deltaTime;
            t.localScale = Vector3.Lerp(start, start * 0.88f, time / 0.12f);
            yield return null;
        }
        time = 0f;
        while (time < 0.12f)
        {
            time += Time.deltaTime;
            t.localScale = Vector3.Lerp(start * 0.88f, start, time / 0.12f);
            yield return null;
        }
        t.localScale = start;
    }
}
