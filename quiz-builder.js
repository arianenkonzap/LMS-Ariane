/**
 * LMS-ARIANE — Ajout dynamique de questions dans le constructeur d'évaluation
 */
(function () {
    const container = document.getElementById('questionsContainer');
    const template = document.getElementById('questionTemplate');
    const addBtn = document.getElementById('addQuestionBtn');

    if (!container || !template || !addBtn) return;

    addBtn.addEventListener('click', function () {
        const index = container.querySelectorAll('[data-question]').length;
        const html = template.innerHTML.replace(/__INDEX__/g, index);

        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const newQuestion = wrapper.firstElementChild;

        // Met à jour le numéro affiché
        const heading = newQuestion.querySelector('h4');
        heading.innerHTML = 'Question ' + (index + 1) +
            '<button type="button" class="btn btn-ghost btn-sm" onclick="this.closest(\'[data-question]\').remove()" style="float:right;">Retirer</button>';

        container.appendChild(newQuestion);
    });
})();
