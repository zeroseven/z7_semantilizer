export class Cast {
    static array(list) {
        return list ? Array.prototype.slice.call(list) : [];
    }
    static string(value) {
        return (value || "").toString();
    }
    static integer(value) {
        return isNaN(value) ? parseInt(value || 0) : Math.round(value);
    }
}