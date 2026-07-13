/**
 * Toggle buttons (mobile nav, pages sidebar)
 */
document.querySelectorAll('[data-toggle]').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelector(button.dataset.toggle)?.classList.toggle('hidden');
    });
});

/**
 * Docs Search
 */
const search_container = document.getElementById('search_container');
const search_field = document.getElementById('searchdocs');
const search_result = document.getElementById('search_result');

let active_index = -1;

const show_results = (show) => search_result.classList.toggle('hidden', !show);

const render_results = (resp) => {
    let result = '';

    if (!resp.status) {
        Array.prototype.forEach.call(resp, data => {
            if (data) {
                if (data.page !== data.title) {
                    result += `<li><a class="block hover:bg-gray-100 focus:bg-gray-100 py-1.5 px-2.5 text-gray-800 hover:text-gray-700 w-full truncate" href="${data.link}">
                                   <span class="font-normal opacity-75">${data.page}</span><br> ${data.title}
                               </a></li>`;
                } else {
                    result += `<li>
                                   <a class="block hover:bg-gray-100 focus:bg-gray-100 py-1.5 px-2.5 text-gray-800 hover:text-gray-700 w-full truncate" href="${data.link}">${data.title}</a>
                               </li>`;
                }
            }
        });
    } else {
        result = `<li><span class="block text-gray-500 py-3 px-2.5 w-full truncate">${resp.status}</span></li>`;
    }

    active_index = -1;
    search_result.innerHTML = result;
};

const set_active = (index) => {
    const items = search_result.querySelectorAll('a');

    if (items.length === 0) {
        return;
    }

    active_index = (index + items.length) % items.length;

    items.forEach((item, i) => item.classList.toggle('bg-gray-100', i === active_index));
    items[active_index].scrollIntoView({block: 'nearest'});
};

if (search_field) {
    let no_items = `<li><span class="block text-gray-500 py-3 px-2.5 w-full truncate">Enter a search term to find results.</span></li>`;

    search_result.innerHTML = no_items;

    search_field.addEventListener('focus', () => show_results(true));

    document.addEventListener('click', (e) => {
        if (!search_container.contains(e.target)) {
            show_results(false);
        }
    });

    search_field.addEventListener('input', () => {
        let request = new XMLHttpRequest();
        request.open('GET', SITEURL + 'search?page=' + encodeURIComponent(search_field.value), true);

        request.onload = function () {
            if (this.status >= 200 && this.status < 400) {
                if (search_field.value === '') {
                    search_result.innerHTML = no_items;
                } else {
                    render_results(JSON.parse(this.response));
                }

                show_results(true);
            }
        };

        request.send();
    });

    search_field.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            set_active(active_index + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            set_active(active_index - 1);
        } else if (e.key === 'Enter') {
            const items = search_result.querySelectorAll('a');
            const active = items[active_index] ?? items[0];

            if (active) {
                window.location.href = active.href;
            }
        } else if (e.key === 'Escape') {
            show_results(false);
        }
    });
}
