using UnityEngine;
using UnityEngine.InputSystem;
using UnityEngine.SceneManagement;

// Used by the temporary room scenes: Escape (or the timer) goes back to the lab.
public class ReturnToScene : MonoBehaviour
{
    public string sceneName = "EscapeRoom_Main";
    public float autoReturnSeconds = 0f;       // 0 = only Escape

    void Update()
    {
        bool esc = Keyboard.current != null && Keyboard.current[Key.Escape].wasPressedThisFrame;
        bool timer = autoReturnSeconds > 0f && Time.timeSinceLevelLoad > autoReturnSeconds;
        if ((esc || timer) && Application.CanStreamedLevelBeLoaded(sceneName)) SceneManager.LoadScene(sceneName);
    }
}
