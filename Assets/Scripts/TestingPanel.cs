using System.Collections.Generic;
using UnityEngine;

public class TestingPanel : MonoBehaviour
{
    [SerializeField] private SimpleKeypad _keypad;
    [SerializeField] private TMPro.TMP_FontAsset _font;
    [SerializeField] private Vector3 _localOffset = new Vector3(-600f, 0f, 0f);
    private Transform _ui;
    private TMPro.TMP_Text _pinDisplay, _expectedBox, _actualBox, _feedback, _log, _doorCode;
    private UnityEngine.UI.Button _run, _pass, _fail, _reset;
    private readonly List<UnityEngine.UI.Button> _inputButtons = new List<UnityEngine.UI.Button>();
    private readonly Queue<string> _entries = new Queue<string>();
    private readonly bool[] _found = new bool[3];
    private string _pin = "", _code, _expected, _actual, _testedPin;
    private int _specFailures, _actualFailures, _bug = -1;
    private bool _awaitingVerdict;
    private static readonly Color Panel = new Color32(11, 15, 20, 255);
    private static readonly Color Key = new Color32(28, 39, 51, 255);
    private static readonly Color Green = new Color32(92, 255, 154, 255);

    private void Start()
    {
        BuildUI();
        _code = Random.Range(100000, 1000000).ToString("D6");
        _keypad.SetCode(_code);
        ResetSystem();
        Reveal();
    }

    private void BuildUI()
    {
        var root = new GameObject("TestingUI", typeof(RectTransform), typeof(Canvas),
            typeof(UnityEngine.UI.CanvasScaler), typeof(UnityEngine.UI.GraphicRaycaster),
            typeof(UnityEngine.XR.Interaction.Toolkit.UI.TrackedDeviceGraphicRaycaster));
        _ui = root.transform;
        _ui.SetParent(transform, false);
        _ui.localPosition = _localOffset;
        _ui.localRotation = Quaternion.Inverse(transform.rotation);
        Vector3 scale = transform.lossyScale;
        _ui.localScale = new Vector3(.001f / scale.x, .001f / scale.y, .001f / scale.z);
        ((RectTransform)_ui).sizeDelta = new Vector2(600f, 800f);
        var canvas = root.GetComponent<Canvas>();
        canvas.renderMode = RenderMode.WorldSpace;
        canvas.sortingOrder = 10;
        canvas.worldCamera = _keypad.GetComponent<Canvas>().worldCamera;
        root.GetComponent<UnityEngine.XR.Interaction.Toolkit.UI.TrackedDeviceGraphicRaycaster>().ignoreReversedGraphics = false;
        Box("Background", _ui, 0, 0, 600, 800, Panel);
        Label("Title", "REQUIREMENTS SPEC", _ui, 20, 15, 560, 42, 30);
        Label("Requirements", "R1 PIN 4721 grants access.\nR2 any other PIN is denied.\nR3 a PIN must be exactly 4 digits.\nR4 after 3 failed attempts the system locks out.", _ui, 20, 62, 560, 126, 23);
        var strip = Box("PINStrip", _ui, 20, 196, 560, 50, Key);
        _pinDisplay = Label("PIN", "PIN: ------", strip, 12, 0, 536, 50, 28);
        _pinDisplay.color = Green;
        for (int i = 0; i < 10; i++)
        {
            string digit = i.ToString();
            _inputButtons.Add(Button(digit, 20 + (i % 5) * 114, 256 + (i / 5) * 66, 104, 56, () => Append(digit)));
        }
        _inputButtons.Add(Button("Clear", 20, 390, 180, 52, Clear));
        _run = Button("Run test", 210, 390, 370, 52, RunTest);
        var expected = Box("ExpectedBox", _ui, 20, 454, 274, 80, Key);
        var actual = Box("ActualBox", _ui, 306, 454, 274, 80, Key);
        _expectedBox = Label("Expected", "EXPECTED\n--", expected, 10, 0, 254, 80, 23);
        _actualBox = Label("Actual", "ACTUAL\n--", actual, 10, 0, 254, 80, 23);
        _pass = Button("PASS", 20, 546, 274, 52, () => Verdict(true));
        _fail = Button("FAIL", 306, 546, 274, 52, () => Verdict(false));
        _feedback = Label("Feedback", "Enter a PIN and run a test.", _ui, 20, 604, 560, 36, 23);
        _feedback.color = Green;
        _log = Label("TestLog", "TEST LOG", _ui, 20, 643, 560, 82, 16);
        _reset = Button("Reset system", 20, 737, 236, 46, ResetSystem);
        _doorCode = Label("DoorCode", "DOOR CODE: -- -- --", _ui, 266, 737, 314, 46, 24);
        _doorCode.color = Green;
    }

    private void Append(string digit)
    {
        if (_awaitingVerdict || _pin.Length >= 6) return;
        _pin += digit; UpdatePIN();
    }
    private void Clear() { if (!_awaitingVerdict) { _pin = ""; UpdatePIN(); } }
    private void UpdatePIN() { _pinDisplay.text = "PIN: " + (_pin.Length == 0 ? "------" : _pin); }

    private static string Evaluate(string pin, ref int failures, bool buggy)
    {
        if (failures >= (buggy ? 4 : 3)) return "LOCKED OUT";
        bool granted = buggy ? pin.TrimStart('0') == "4721" || pin == "472" : pin == "4721";
        if (granted) return "ACCESS GRANTED";
        failures++;
        return "ACCESS DENIED";
    }

    private void RunTest()
    {
        if (_awaitingVerdict) return;
        _testedPin = _pin;
        _expected = Evaluate(_pin, ref _specFailures, false);
        _actual = Evaluate(_pin, ref _actualFailures, true);
        _bug = -1;
        if (_expected != _actual)
        {
            if (_expected == "LOCKED OUT") _bug = 2;
            else if (_pin == "472") _bug = 0;
            else if (_pin.StartsWith("0") && _pin.TrimStart('0') == "4721") _bug = 1;
        }
        _expectedBox.text = "EXPECTED\n" + _expected;
        _actualBox.text = "ACTUAL\n" + _actual;
        _feedback.text = "Choose PASS or FAIL before the next test.";
        _awaitingVerdict = true; UpdateButtons();
    }

    private void Verdict(bool pass)
    {
        if (!_awaitingVerdict) return;
        bool mismatch = _expected != _actual;
        string feedback = mismatch ? (pass ? "Missed bug" : "Bug confirmed") : (pass ? "Correct" : "False alarm");
        if (mismatch && !pass && _bug >= 0 && !_found[_bug])
        {
            _found[_bug] = true; Reveal();
        }
        _feedback.text = feedback;
        _entries.Enqueue((_testedPin.Length == 0 ? "(empty)" : _testedPin) + " | " + (pass ? "PASS" : "FAIL") + " | " + feedback);
        while (_entries.Count > 3) _entries.Dequeue();
        _log.text = "TEST LOG\n" + string.Join("\n", _entries);
        _awaitingVerdict = false; _pin = ""; UpdatePIN(); UpdateButtons();
    }

    private void ResetSystem()
    {
        if (_awaitingVerdict) return;
        _specFailures = _actualFailures = 0; _pin = "";
        _expectedBox.text = "EXPECTED\n--"; _actualBox.text = "ACTUAL\n--";
        _feedback.text = "System reset. Enter a PIN and run a test.";
        UpdatePIN(); UpdateButtons();
    }

    private void UpdateButtons()
    {
        _run.interactable = _reset.interactable = !_awaitingVerdict;
        _pass.interactable = _fail.interactable = _awaitingVerdict;
        foreach (var button in _inputButtons) button.interactable = !_awaitingVerdict;
    }
    private void Reveal()
    {
        _doorCode.text = "DOOR CODE: " + (_found[0] ? _code.Substring(0, 2) : "--") + " "
            + (_found[1] ? _code.Substring(2, 2) : "--") + " " + (_found[2] ? _code.Substring(4, 2) : "--");
    }

    private RectTransform Rect(string name, Transform parent, float x, float y, float width, float height)
    {
        var rect = (RectTransform)new GameObject(name, typeof(RectTransform)).transform;
        rect.SetParent(parent, false);
        rect.anchorMin = rect.anchorMax = new Vector2(0f, 1f);
        rect.pivot = new Vector2(0f, 1f);
        rect.anchoredPosition = new Vector2(x, -y); rect.sizeDelta = new Vector2(width, height);
        return rect;
    }
    private RectTransform Box(string name, Transform parent, float x, float y, float w, float h, Color color)
    {
        var rect = Rect(name, parent, x, y, w, h);
        var image = rect.gameObject.AddComponent<UnityEngine.UI.Image>();
        image.color = color; image.raycastTarget = false;
        return rect;
    }
    private TMPro.TMP_Text Label(string name, string value, Transform parent, float x, float y, float w, float h, float size)
    {
        var rect = Rect(name, parent, x, y, w, h);
        var text = rect.gameObject.AddComponent<TMPro.TextMeshProUGUI>();
        text.font = _font; text.text = value; text.fontSize = size; text.enableAutoSizing = false;
        text.color = Color.white; text.alignment = TMPro.TextAlignmentOptions.MidlineLeft;
        text.raycastTarget = false;
        return text;
    }
    private UnityEngine.UI.Button Button(string caption, float x, float y, float w, float h, UnityEngine.Events.UnityAction action)
    {
        var rect = Box(caption, _ui, x, y, w, h, Key);
        var image = rect.GetComponent<UnityEngine.UI.Image>(); image.raycastTarget = true;
        var button = rect.gameObject.AddComponent<UnityEngine.UI.Button>(); button.targetGraphic = image;
        var colors = button.colors;
        colors.highlightedColor = new Color(1.5f, 1.5f, 1.5f, 1f);
        colors.pressedColor = new Color(.5f, 1f, .7f, 1f); button.colors = colors;
        button.onClick.AddListener(action);
        Label("Label", caption, rect, 0, 0, w, h, 28).alignment = TMPro.TextAlignmentOptions.Center;
        return button;
    }
}
