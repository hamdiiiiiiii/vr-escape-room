using System.Collections;
using UnityEngine;
using UnityEngine.Events;

public class SimpleKeypad : MonoBehaviour
{
    public UnityEvent onUnlocked = new UnityEvent();
    private TMPro.TMP_Text _display;
    private string _code = "", _input = "";
    private bool _busy, _open;
    private const int MaxDigits = 6;

    private void Awake()
    {
        var canvas = GetComponent<Canvas>();
        if (canvas.worldCamera == null) canvas.worldCamera = Camera.main;
        foreach (var text in GetComponentsInChildren<TMPro.TMP_Text>(true))
            if (text.name == "Display") _display = text;
        var buttons = GetComponentsInChildren<UnityEngine.UI.Button>(true);
        string[] keys = { "1", "2", "3", "4", "5", "6", "7", "8", "9", "C", "0", "OK" };
        for (int i = 0; i < buttons.Length && i < keys.Length; i++)
        {
            string key = keys[i];
            buttons[i].onClick.AddListener(() => Press(key));
        }
        Refresh();
    }

    public void SetCode(string code)
    {
        if (code == null || code.Length != MaxDigits) throw new System.ArgumentException("Code must contain six digits.");
        foreach (char c in code) if (c < '0' || c > '9') throw new System.ArgumentException("Code must contain six digits.");
        StopAllCoroutines();
        _code = code; _input = ""; _busy = _open = false;
        Refresh();
    }

    private void Press(string key)
    {
        Debug.Log("Keypad press: " + key, this);
        if (_busy || _open) return;
        if (key == "C") { if (_input.Length > 0) _input = _input.Substring(0, _input.Length - 1); }
        else if (key == "OK")
        {
            if (_code.Length == MaxDigits && _input == _code)
            {
                _open = true; Show("OPEN"); onUnlocked.Invoke();
            }
            else StartCoroutine(Wrong());
            return;
        }
        else if (_input.Length < MaxDigits) _input += key;
        Refresh();
    }

    private IEnumerator Wrong()
    {
        _busy = true; Show("WRONG");
        yield return new WaitForSecondsRealtime(1f);
        _input = ""; _busy = false; Refresh();
    }

    private void Refresh() { Show(_input.Length == 0 ? "------" : _input); }
    private void Show(string value)
    {
        if (_display != null)
            _display.text = "<mark=#123523FF><mspace=28> " + value.PadRight(MaxDigits) + " </mspace></mark>";
    }
}
