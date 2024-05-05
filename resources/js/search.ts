const search_form = document.getElementById('search') as HTMLFormElement;

export function initialize_search() {
    if (search_form === null) {
        return ;
    }
    search_form.addEventListener('submit', on_search_input_change);
}

function on_search_input_change(event: Event) {
    event.preventDefault();
    const $search = search_form.elements.namedItem('text');
    if (!($search instanceof HTMLInputElement)) {
        return;
    }
    const value = $search.value;
    if (value.match(/[^a-zA-T0-9_ -]/)) {
        return ;
    }
    // Get the current query string
    const current_search = window.location.search.match(/search=([^&]*)/)?.[0] ?? null;
    const assignment = 'search=' + encodeURIComponent(value);
    if (current_search === null) {
        if (window.location.search === '') {
            const new_url = window.location.href + '?' + assignment;
            console.log(new_url);
            window.location.href = new_url;
            return ;
        } else {
            const new_url = window.location.href + '&' + assignment;
            console.log(new_url);
            window.location.href = new_url;
            return ;
        }
    }
    const new_url = window.location.href.replace(current_search, assignment);
    console.log(new_url);
    window.location.href = new_url;
    return ;
}
