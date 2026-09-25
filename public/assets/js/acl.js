document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.acl-form').forEach(function (form) {
        form.querySelectorAll('input[name="subject_type"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var selected = form.querySelector('input[name="subject_type"]:checked').value;
                form.querySelectorAll('.acl-subjects').forEach(function (box) {
                    var active = box.dataset.subject === selected;
                    box.classList.toggle('hidden', !active);
                    box.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                        cb.checked = active ? cb.checked : false;
                        cb.disabled = !active;
                    });
                });
            });
        });
        var checked = form.querySelector('input[name="subject_type"]:checked');
        if (checked) checked.dispatchEvent(new Event('change'));
    });
});