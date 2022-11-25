window.addEventListener('DOMContentLoaded', function () {
    elements = document.querySelectorAll("[data-search='true']");
    console.log(elements);
    elements.forEach(searchElement => {
        console.log(searchElement);
        searchElement.addEventListener('focus', function (evt) {
            evt.target.nextSibling.nextSibling.style.visibility = 'visible';
        });
        searchElement.addEventListener('blur', function (evt) {
            evt.target.nextSibling.nextSibling.style.visibility = 'hidden';
        });
        let searcResults = [];
        let allSearchResults = [];
        let data = getDataAttributes(searchElement);
        let pointer = data.pointer || -1;
        let resContId = data.restarget;
        let cont = document.querySelector('#' + resContId);
        let debounceTime = +data.debounce;
        searchElement.addEventListener('keydown', function (e) {
            if (e.keyCode === 8) {
                cont.innerHTML = "";
                pointer = -1;
            }

            if (e.keyCode === 27) {
                searchElement.value = "";
                cont.innerHTML = "";
                pointer = -1;
                return false;
            }

            if (e.keyCode === 38) {//UP (-)
                e.stopPropagation();
                e.preventDefault();
                if (pointer > 0) {
                    pointer--;
                } else {
                    pointer = -1;
                }
                searchElement.dataset.pointer = pointer;

                cont.childNodes.forEach((child, index) => {
                    child.classList.remove("active");
                    if (index == pointer) {
                        child.classList.add("active");
                    }
                })

                return false;
            }

            if (e.keyCode === 40) {//down(+)
                e.stopPropagation();
                e.preventDefault();
                if (pointer < searcResults.length - 1) {
                    pointer++;
                } else {
                    pointer = searcResults.length - 1;
                }
                searchElement.dataset.pointer = pointer;

                cont.childNodes.forEach((child, index) => {
                    child.classList.remove("active");
                    if (index == pointer) {
                        child.classList.add("active");
                    }
                })

                return false;
            }

            if (e.keyCode === 13) {
                e.stopPropagation();
                e.preventDefault();

                // if pointer ==-1
                if (pointer == -1) {

                    // if app & cb2
                    if (window[data.application] && typeof (window[data.application][data.callback2]) === "function") {
                        let myfunc = window[data.application][data.callback2];
                        allSearchResults.sort(function (a, b) {
                            return a['servicesName'].localeCompare(b['servicesName']);
                            });
                        myfunc.apply(null, [allSearchResults]);
                    }

                    // if !app & cb2
                    if (!window[data.application] && typeof (window[data.callback2]) === "function") {
                        let myfunc = window[data.callback2];
                        myfunc.apply(null, [searchElement.value]);
                    }
                }

                // if pointer !==-1
                else {
                    if (window[data.application] && typeof (window[data.application][data.callback]) === "function") {
                        let myfunc = window[data.application][data.callback];
                        myfunc.apply(null, [searcResults[pointer]]);
                    }

                    if (!window[data.application] && typeof (window[data.callback]) === "function") {
                        let myfunc = window[data.callback];
                        myfunc.apply(null, [searcResults[pointer]]);
                    }
                }

                searchElement.value = "";
                cont.innerHTML = "";
                pointer = -1;
                return false;
            }
        });

        searchElement.addEventListener('keyup', function (e) {
            if (searchElement.value.length > 2 && !(
                e.keyCode === 38 || e.keyCode === 40
            )) {
                debounce(query, debounceTime);
            }
        })

        let debounceTimer;
        const debounce = (callback, time) => {
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(callback, time);
        }

        function query() {
            fetch('/sync/api/local/services/suggestions.php', {
                body: JSON.stringify({ search: searchElement.value, displayDeletedServices: (typeof servicesApp.displayDeletedServices !== 'undefined') ? servicesApp.displayDeletedServices : false }),
                credentials: 'include',
                method: 'POST', headers: new Headers({ 'Content-Type': 'application/json' })
            }).then(result => result.text()).then(function (text) {
                try {
                    let jsn = JSON.parse(text);
                    if (jsn.success) {
                        cont.innerHTML = "";
                        searcResults = [];
                        pointer = -1;
                        
                        allSearchResults = jsn.services;
                        // Sorting by servicesName
                        jsn.services.sort(SortArray);
                        if (jsn.services.length > 10) {
                            for (let index = 0; index < 10; index++) { 
                                if (jsn.services[index].servicesNameHighlighted.length > 150) {
                                    let str = truncate(jsn.services[index].servicesNameHighlighted, 150);
                                    jsn.services[index].servicesNameHighlighted = str;
                                }
                                jsn.services[index].title = jsn.services[index].servicesName;
                                searcResults.push(jsn.services[index]);
                            }
                        } else {
                            for (let index = 0; index < jsn.services.length; index++) {
                                if (jsn.services[index].servicesNameHighlighted.length > 150) {
                                    let str = truncate(jsn.services[index].servicesNameHighlighted, 150);
                                    jsn.services[index].servicesNameHighlighted = str;
                                }
                                jsn.services[index].title = jsn.services[index].servicesName;
                                searcResults.push(jsn.services[index]);
                            }
                        }

                        searcResults.forEach((element, i) => {
                            let li = el('li', {
                                innerHTML: `<span>${element.servicesNameHighlighted}</span>`,
                                className: 'collection-item cursor'
                            });
                            li.title = element.title;
                            li.addEventListener('mousedown', function () {
                                // if input have data-application sttr
                                if (data.application) {
                                    if (window[data.application] && typeof (window[data.application][data.callback]) === "function") {
                                        let myfunc = window[data.application][data.callback];
                                        myfunc.apply(null, [element]);
                                        searchElement.value = "";
                                        cont.innerHTML = "";
                                    } else {
                                        MSG({ type: 'neutral', text: `Данный функционал ещё<br> не реализован :( <br> Функция <i>${data.callback}</i> <br>всё ещё в разработке.` });
                                    }
                                } else {
                                    if (!window[data.application] && typeof (window[data.callback]) === "function") {
                                        let myfunc = window[data.callback];
                                        myfunc.apply(null, [element]);
                                        searchElement.value = "";
                                        cont.innerHTML = "";
                                    } else {
                                        MSG({ type: 'neutral', text: `Данный функционал ещё<br> не реализован :( <br> Функция <i>${data.callback}</i> <br>всё ещё в разработке.` });
                                    }
                                }
                            });
                            cont.appendChild(li);
                        });
                    } else {
                        if (searchElement.value) {
                            cont.innerHTML = "";
                            let markup = `<li class="collection-item">
                                        <span>"${e.target.value}" не найдено</span>
                                    </li>`;
                            cont.innerHTML = (cont.innerHTML || "") + markup;
                        } else {
                            cont.innerHTML = "";
                        }
                    }
                } catch (e) {
                    console.log('no');
                    console.log(e);
                }
            });
        }
    });

    function SortArray(x, y) {
        return x.servicesName.localeCompare(y.servicesName);
    }

    function truncate(str, n) {
        return (str.length > n) ? str.slice(0, n - 1) + '&hellip;' : str;
    };
});