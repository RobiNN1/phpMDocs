/**
 * Docs Search
 */
let search_field = document.getElementById('searchdocs');
let search_result = document.getElementById('search_result');

if (search_field) {
    search_field.addEventListener('keyup', () => {
        let request = new XMLHttpRequest();
        request.open('GET', SITEURL + 'search?page=' + search_field.value, true);

        request.onload = function () {
            if (this.status >= 200 && this.status < 400) {
                if (search_field.value === '') {
                    search_result.classList.remove('show');
                } else {
                    let resp = JSON.parse(this.response);
                    let result = '';

                    if (!resp.status) {
                        Array.prototype.forEach.call(resp, (data) => {
                            if (data) {
                                if (data.page !== data.title) {
                                    result += `<li><a class="dropdown-item text-truncate" href="${data.link}">
                                            <span class="fw-light opacity-75">${data.page}</span><br> ${data.title}
                                        </a></li>`;
                                } else {
                                    result += `<li><a class="dropdown-item text-truncate" href="${data.link}">${data.title}</a></li>`;
                                }
                            }
                        });
                    } else {
                        result = `<li><span class="dropdown-header fw-bold text-truncate">${resp.status}</span></li>`;
                    }

                    search_result.innerHTML = result;
                    search_result.classList.add('show');
                }
            }
        };

        request.send();
    });
}
