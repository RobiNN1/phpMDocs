/**
 * Docs Search
 */
let search_field = document.getElementById('searchdocs');
let search_result = document.getElementById('search_result');

if (search_field) {
    let no_items = `<li>
    <span class="block text-gray-500 py-3 px-2.5 truncate">Enter a search term to find results.</span>
    </li>`;
    search_result.innerHTML = no_items;

    search_field.addEventListener('keyup', () => {
        let request = new XMLHttpRequest();
        request.open('GET', SITEURL + 'search?page=' + search_field.value, true);

        request.onload = function () {
            if (this.status >= 200 && this.status < 400) {
                if (search_field.value === '') {
                    search_result.style.display = 'none';
                    search_result.innerHTML = no_items;
                } else {
                    render_results(JSON.parse(this.response));
                    search_result.style.display = 'block';
                }
            }
        };

        request.send();
    });
}

const render_results = (resp) => {
    let result = '';

    if (!resp.status) {
        Array.prototype.forEach.call(resp, (data) => {
            if (data) {
                if (data.page !== data.title) {
                    result += `<li><a class="block hover:bg-gray-100 py-1.5 px-2.5 text-gray-800 hover:text-gray-700 truncate" href="${data.link}">
                                    <span class="font-normal opacity-75">${data.page}</span><br> ${data.title}
                                </a></li>`;
                } else {
                    result += `<li><a class="block hover:bg-gray-100 py-1.5 px-2.5 text-gray-800 hover:text-gray-700 truncate" href="${data.link}">${data.title}</a></li>`;
                }
            }
        });
    } else {
        result = `<li><span class="block text-gray-500 py-3 px-2.5 truncate">${resp.status}</span></li>`;
    }

    search_result.innerHTML = result;
};
