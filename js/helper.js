/**
 * Задать Адрес страницы без перезагрузки
 * @param column
 * @param value
 * @returns {boolean}
 */
function setLocationUrlParam(column, value) {
    const url = new URL(window.location.href);

    if (!value || !value.length) {
        url.searchParams.delete(column);
    }
    else {
        url.searchParams.set(column, value);
    }

    window.history.replaceState(null, null, url);
    return true;
}


/**
 * Поиск параметра из URL
 * @param parameterName
 * @returns {*}
 */
function findGetParameter(parameterName) {
    let result = null,
        tmp = [];
    let items = location.search.substr(1).split("&");
    for (let index = 0; index < items.length; index++) {
        tmp = items[index].split("=");
        if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
    }
    return result;
}


function makeid(length) {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;
    for (let i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
}

function checkNumberInterval(number_mixed, min = 1, max, type = 'int') {

    let number = 0;

    if (type == 'int') {
        number = parseInt(number_mixed);
    }
    else {
        number = parseFloat(number_mixed);
    }
    switch (true) {
        case isNaN(number):
        case number < min:
            number = min;
            break;
        case number > max:
            number = max;
            break;
    }
    return number;
}

/**
 * detect IE
 * returns version of IE or false, if browser is not Internet Explorer
 */
function detectIE() {
    const ua = window.navigator.userAgent;

    const msie = ua.indexOf('MSIE ');
    if (msie > 0) {
        // IE 10 or older => return version number
        return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
    }

    const trident = ua.indexOf('Trident/');
    if (trident > 0) {
        // IE 11 => return version number
        const rv = ua.indexOf('rv:');
        return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10);
    }

    const edge = ua.indexOf('Edge/');
    if (edge > 0) {
        // Edge (IE 12+) => return version number
        return parseInt(ua.substring(edge + 5, ua.indexOf('.', edge)), 10);
    }

    // other browser
    return false;
}



function getBrowser() {
    let browser = '';

    if ((navigator.userAgent.indexOf("Opera") || navigator.userAgent.indexOf('OPR')) != -1) {
        browser = 'Opera';
    }
    else if (navigator.userAgent.indexOf("Chrome") != -1) {
        browser = 'Chrome';
    }
    else if (navigator.userAgent.indexOf("Safari") != -1) {
        browser = 'Safari';
    }
    else if (navigator.userAgent.indexOf("Firefox") != -1) {
        browser = 'Firefox';
    }
    else if ((navigator.userAgent.indexOf("MSIE") != -1) || (!!document.documentMode == true)) //IF IE > 10
    {
        browser = 'IE';
    }
    else {
        browser = 'unknown';
    }
    return browser;
}


function isEmpty(obj) {
    for (let key in obj) {
        if (obj.hasOwnProperty(key))
            return false;
    }
    return true;
}


// Returns if a value is an object
function isObject(value) {
    return value && typeof value === 'object' && value.constructor === Object;
}


function navConfirm(loc, message = null) {
    if (!message) {
        message = "Are you sure?";
    }

    if (confirm(message)) {
        window.location.href = loc;
    }
    return false;
}
