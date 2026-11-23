using UnityEngine;
using UnityEngine.InputSystem;

// Lives on the XR Origin. Looks at what the player is facing: press E (or F) at a pedestal to open its keypad,
// Escape closes it. Walking is switched off while a keypad is open. The keys also work with the XR Interaction
// Simulator; in a headset the player can instead point at the keypad cover and pull the trigger.
public class PlayerInteraction : MonoBehaviour
{
    public Camera playerCamera;
    public GameObject locomotion;            // the XR Origin's Locomotion object (walking / turning)
    public float reach = 3f;
    public Key interactKey = Key.E;
    public Key altInteractKey = Key.F;
    public Key closeKey = Key.Escape;

    AccessPedestal focused, active;

    void OnEnable() { AccessPedestal.ActiveChanged += OnActiveChanged; }
    void OnDisable() { AccessPedestal.ActiveChanged -= OnActiveChanged; }

    void Start() { if (playerCamera == null) playerCamera = GetComponentInChildren<Camera>(); }

    void Update()
    {
        Keyboard kb = Keyboard.current;

        if (active != null)
        {
            if (kb != null && kb[closeKey].wasPressedThisFrame) active.Close();
            return;
        }
        if (playerCamera == null) return;

        focused = null;
        Transform t = playerCamera.transform;
        if (Physics.Raycast(t.position, t.forward, out RaycastHit hit, reach, ~0, QueryTriggerInteraction.Ignore))
            focused = hit.collider.GetComponentInParent<AccessPedestal>();

        if (focused != null && kb != null && (kb[interactKey].wasPressedThisFrame || kb[altInteractKey].wasPressedThisFrame))
            focused.Open();
    }

    void OnActiveChanged(AccessPedestal p)
    {
        active = p;
        if (locomotion != null) locomotion.SetActive(p == null);     // lock movement while a keypad is open
    }
}
