using System.Collections;
using System.Text;
using TMPro;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

// The home screen logic.
//  - The wall keypad is always usable: type the 6-digit code from the website, press OK.
//  - The wall monitor shows the welcome text, then the room details and a START button.
//  - When the server accepts the code, the vault door opens.
public class HomeMenu : MonoBehaviour
{
    [Header("Server")]
    [SerializeField] string baseUrl = "http://localhost:8000";

    [Header("Scene to load when the player presses Start")]
    [SerializeField] string gameSceneName = "Museum Main";

    [Header("Wall monitor")]
    [SerializeField] GameObject welcomePanel;
    [SerializeField] GameObject confirmPanel;
    [SerializeField] TMP_Text roomNameText;
    [SerializeField] TMP_Text roomInfoText;
    [SerializeField] Button startButton;

    [Header("Wall keypad")]
    [SerializeField] TMP_Text codeText;
    [SerializeField] TMP_Text messageText;
    [SerializeField] Button keyTemplate;        // one button; the script makes the 12 keys from it
    [SerializeField] Transform keypadRoot;      // the object with the Grid Layout Group

    [Header("Vault door (optional)")]
    [SerializeField] VaultDoorController vaultDoor;

    static readonly Color Normal = new Color(0.25f, 0.88f, 0.77f);
    static readonly Color Error = new Color(1f, 0.36f, 0.36f);
    static readonly Color Good = new Color(0.29f, 0.87f, 0.5f);

    string code = "";
    bool busy;
    bool unlocked;

    void Awake()
    {
        SessionData.BaseUrl = baseUrl;

        if (startButton != null) startButton.onClick.AddListener(StartGame);

        BuildKeypad();
        Refresh();
        SetMessage("ENTER THE 6-DIGIT CODE", Normal);
        ShowMonitor(false);
    }

    void BuildKeypad()
    {
        if (keyTemplate == null || keypadRoot == null)
        {
            Debug.LogWarning("HomeMenu: fill 'Key Template' and 'Keypad Root' in the Inspector.");
            return;
        }

        keyTemplate.gameObject.SetActive(false);

        string[] labels = { "1", "2", "3", "4", "5", "6", "7", "8", "9", "C", "0", "OK" };
        foreach (string label in labels)
        {
            Button key = Instantiate(keyTemplate, keypadRoot);
            key.gameObject.name = "Key_" + label;
            key.gameObject.SetActive(true);

            TMP_Text text = key.GetComponentInChildren<TMP_Text>();
            if (text != null) text.text = label;

            string pressed = label;
            key.onClick.AddListener(() => OnKey(pressed));
        }
    }

    void ShowMonitor(bool granted)
    {
        if (welcomePanel != null) welcomePanel.SetActive(!granted);
        if (confirmPanel != null) confirmPanel.SetActive(granted);
    }

    void OnKey(string key)
    {
        if (busy || unlocked) return;

        if (key == "C")
        {
            code = "";
            SetMessage("ENTER THE 6-DIGIT CODE", Normal);
        }
        else if (key == "OK")
        {
            Submit();
            return;
        }
        else if (code.Length < 6)
        {
            code += key;
        }

        Refresh();
    }

    void Refresh()
    {
        if (codeText == null) return;

        var sb = new StringBuilder();
        for (int i = 0; i < 6; i++)
        {
            sb.Append(i < code.Length ? code[i] : '-');
            if (i < 5) sb.Append(' ');
        }
        codeText.text = sb.ToString();
    }

    void SetMessage(string text, Color color)
    {
        if (messageText == null) return;
        messageText.text = text;
        messageText.color = color;
    }

    void Submit()
    {
        if (code.Length != 6)
        {
            SetMessage("ENTER ALL 6 DIGITS", Error);
            return;
        }
        StartCoroutine(VerifyRoutine());
    }

    IEnumerator VerifyRoutine()
    {
        busy = true;
        SetMessage("CHECKING...", Normal);

        VerifyResponse result = null;
        string error = null;
        yield return ApiClient.VerifyCode(code, (r, e) => { result = r; error = e; });

        busy = false;

        if (result == null)
        {
            code = "";
            Refresh();
            SetMessage((error ?? "SOMETHING WENT WRONG").ToUpper(), Error);
            yield break;
        }

        unlocked = true;
        SetMessage("ACCESS GRANTED", Good);

        if (roomNameText != null) roomNameText.text = result.room.name;
        if (roomInfoText != null)
        {
            int minutes = Mathf.Max(1, result.room.timeLimitSeconds / 60);
            roomInfoText.text = "TIME LIMIT: " + minutes + " MINUTES";
        }
        ShowMonitor(true);

        if (vaultDoor != null) vaultDoor.Open();
    }

    void StartGame()
    {
        if (!SessionData.HasSession)
        {
            Debug.LogWarning("HomeMenu: no session yet. Enter a code first.");
            return;
        }

        if (!Application.CanStreamedLevelBeLoaded(gameSceneName))
        {
            Debug.LogError("HomeMenu: scene '" + gameSceneName + "' is not in Build Settings. Add it with File > Build Profiles.");
            return;
        }

        SceneManager.LoadScene(gameSceneName);
    }
}
