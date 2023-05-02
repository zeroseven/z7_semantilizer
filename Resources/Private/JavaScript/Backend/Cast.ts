export class Cast {
  static array(list: any): any[] {
    return list ? Array.prototype.slice.call(list) : [];
  }

  static string(value: any): string {
    return (value || '').toString();
  }

  static integer(value: any): number {
    return isNaN(value) ? parseInt(value || 0) : Math.round(value);
  }
}
