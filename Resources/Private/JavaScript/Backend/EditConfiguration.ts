import {Cast} from "@zeroseven/semantilizer/Cast.js";

export class EditConfiguration {
  public table: string;
  public uid: number;
  public field: string;
  public relationId: string;
  public relatedTo: string;

  constructor(node: HTMLElement) {
    let editConfigData = {} as { [index: string]: string | number };

    if (node.dataset.semantilizer) {
      try {
        editConfigData = JSON.parse(node.dataset.semantilizer);
      } catch (e) {
        typeof console.log === 'function' && console.log(e, 1640904719);
      }
    }

    this.table = Cast.string(editConfigData['table']);
    this.uid = Cast.integer(editConfigData['uid']);
    this.field = Cast.string(editConfigData['field']);
    this.relationId = Cast.string(editConfigData['relationId']);
    this.relatedTo = Cast.string(editConfigData['relatedTo']);
  }
}
