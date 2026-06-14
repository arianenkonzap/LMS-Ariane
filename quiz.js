/**
 * LMS-ARIANE — Soumission AJAX de l'évaluation d'une leçon
 */
(function () {
    const quizForm = document.getElementById('quizForm');
    const messages = document.getElementById('quizMessages');

    function afficherMessage(html, type) {
        if (!messages) return;
        messages.innerHTML = '<div class="alert alert-' + type + '">' + html + '</div>';
    }

    if (quizForm) {
        quizForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(quizForm);
            const reponses = {};

            // Transformer question_<id> -> reponses[id]
            for (const [key, value] of formData.entries()) {
                if (key.startsWith('question_')) {
                    const questionId = key.replace('question_', '');
                    reponses[questionId] = value;
                }
            }

            const params = new URLSearchParams();
            params.append('lecon_id', LECON_ID);
            for (const [qId, rId] of Object.entries(reponses)) {
                params.append('reponses[' + qId + ']', rId);
            }

            const submitBtn = quizForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Correction en cours...';

            fetch('/LMS-ARIANE/etudiant/ajax_evaluation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            })
                .then(res => res.json())
                .then(data => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Valider mes réponses';

                    if (data.success) {
                        let html = 'Évaluation corrigée ! Votre score : <strong>' + data.note + '%</strong>.';
                        if (data.certificat) {
                            html += ' 🎉 Félicitations, vous avez validé ce module et obtenu un certificat !';
                        }
                        afficherMessage(html, 'success');

                        setTimeout(() => {
                            window.location.href = 'modules.php?id=' + MODULE_ID;
                        }, 1800);
                    } else {
                        afficherMessage(data.message || 'Une erreur est survenue.', 'error');
                    }
                })
                .catch(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Valider mes réponses';
                    afficherMessage('Erreur réseau. Merci de réessayer.', 'error');
                });
        });
    }

    // Bouton "marquer comme terminée" pour les leçons sans évaluation
    const validerSansEval = document.getElementById('validerSansEval');
    if (validerSansEval) {
        validerSansEval.addEventListener('click', function () {
            validerSansEval.disabled = true;
            validerSansEval.textContent = 'Validation...';

            const params = new URLSearchParams();
            params.append('lecon_id', LECON_ID);

            fetch('/LMS-ARIANE/etudiant/ajax_terminer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        setTimeout(() => {
                            window.location.href = 'modules.php?id=' + MODULE_ID;
                        }, 400);
                    } else {
                        validerSansEval.disabled = false;
                        validerSansEval.textContent = 'Marquer comme terminée (100%)';
                        alert(data.message || 'Une erreur est survenue.');
                    }
                });
        });
    }
})();
