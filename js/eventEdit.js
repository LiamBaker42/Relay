document.addEventListener('DOMContentLoaded', () => {
    const goalList = document.getElementById('goalList');
    const addGoalButton = document.getElementById('addGoal');

    // Add goal functionality
    addGoalButton.addEventListener('click', function () {
        const listItem = document.createElement('li');
        listItem.innerHTML = `
            <input type="text" name="goals[]" placeholder="Enter goal">
            <button type="button" class="remove-goal">Remove</button>
        `;
        goalList.appendChild(listItem);
    });

    // Remove goal functionality
    goalList.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-goal')) {
            const listItem = event.target.parentNode;
            listItem.remove();
        }
    });
});
