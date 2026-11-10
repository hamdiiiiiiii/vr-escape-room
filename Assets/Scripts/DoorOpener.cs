using System.Collections;
using UnityEngine;

public class DoorOpener : MonoBehaviour
{
    [SerializeField] private Transform _hinge;
    [SerializeField] private float _angle = 90f;
    [SerializeField] private float _duration = 1f;
    private bool _opened;

    public void Open()
    {
        if (_opened) return;
        _opened = true;
        StartCoroutine(Rotate());
    }

    private IEnumerator Rotate()
    {
        var hinge = _hinge == null ? transform : _hinge;
        Quaternion start = hinge.localRotation;
        Quaternion end = start * Quaternion.Euler(0f, _angle, 0f);
        float elapsed = 0f;
        while (elapsed < _duration)
        {
            elapsed += Time.deltaTime;
            hinge.localRotation = Quaternion.Slerp(start, end, Mathf.Clamp01(elapsed / _duration));
            yield return null;
        }
        hinge.localRotation = end;
    }
}
