document.addEventListener('DOMContentLoaded', () => {
    const currentMonthSpan = document.getElementById('currentMonth');
    const calendarContainer = document.getElementById('calendar');

    // Get month and year from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    let month = parseInt(urlParams.get('month')) || new Date().getMonth() + 1; // Default to current month if not provided
    let year = parseInt(urlParams.get('year')) || new Date().getFullYear(); // Default to current year if not provided

    // Function to render the calendar
    function renderCalendar(month, year) {
        // Set current month/year text
        currentMonthSpan.textContent = `${new Date(year, month - 1).toLocaleString('default', { month: 'long' })} ${year}`;

        // First day of the month
        const firstDayOfMonth = new Date(year, month - 1, 1);
        const lastDayOfMonth = new Date(year, month, 0);

        // Number of days in the month
        const totalDaysInMonth = lastDayOfMonth.getDate();

        // First day of the week
        const firstDayOfWeek = firstDayOfMonth.getDay();

        // Create the calendar grid
        let html = '<table class="calendar-table"><thead><tr>';
        ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(day => {
            html += `<th>${day}</th>`;
        });
        html += '</tr></thead><tbody><tr>';

        // Empty cells for the first row (before the first day of the month)
        for (let i = 0; i < firstDayOfWeek; i++) {
            html += '<td></td>';
        }

        // Generate each day of the month
        for (let day = 1; day <= totalDaysInMonth; day++) {
            // Check if the day has events
            const eventDay = events.filter(event => {
                const eventDate = new Date(event.EventTime);
                return eventDate.getDate() === day && eventDate.getMonth() === month - 1 && eventDate.getFullYear() === year;
            });

            // Create HTML for the day and event details if any
            const eventHTML = eventDay.map(event => `
                <div class="event">${event.EventDetails}</div>
            `).join('');

            html += `<td>${day}${eventHTML}</td>`;

            if ((day + firstDayOfWeek) % 7 === 0) {
                html += '</tr><tr>'; // New row after Saturday
            }
        }

        // Fill in remaining empty cells
        const remainingCells = (7 - ((totalDaysInMonth + firstDayOfWeek) % 7)) % 7;
        for (let i = 0; i < remainingCells; i++) {
            html += '<td></td>';
        }

        html += '</tr></tbody></table>';
        calendarContainer.innerHTML = html;

        // Adjust the height of calendar cells based on the viewport
        adjustCellHeight();
    }

    // Adjust the height of the calendar cells based on the available height
    function adjustCellHeight() {
        const viewportHeight = window.innerHeight;
        const headerHeight = document.querySelector('header')?.offsetHeight || 0;
        const footerHeight = document.querySelector('footer')?.offsetHeight || 0;

        // Calculate the height available for the calendar
        const availableHeight = viewportHeight - headerHeight - footerHeight;

        // Find the rows in the calendar table
        const rows = calendarContainer.querySelectorAll('tbody tr');

        // Dynamically calculate and set the height for each row
        const minRowHeight = Math.floor(availableHeight / rows.length);

        rows.forEach(row => {
            row.style.height = `${minRowHeight}px`;
        });
    }

    // Event listeners for month navigation
    document.getElementById('prevMonth').addEventListener('click', () => {
        // Update the month and year and reload the page
        if (month === 1) {
            month = 12;
            year--;
        } else {
            month--;
        }
        window.location.search = `?month=${month}&year=${year}`;
    });

    document.getElementById('nextMonth').addEventListener('click', () => {
        // Update the month and year and reload the page
        if (month === 12) {
            month = 1;
            year++;
        } else {
            month++;
        }
        window.location.search = `?month=${month}&year=${year}`;
    });

    // Initial render
    renderCalendar(month, year);

    // Recalculate heights on window resize
    window.addEventListener('resize', adjustCellHeight);
});
