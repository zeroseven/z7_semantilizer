class Issue{constructor(e,i,s){this.key=e,this.headline=i,this.suggestion=s||0}fix(e){this.suggestion&&this.headline.isEditableType()&&(this.headline.type=this.suggestion,!0===e)&&this.headline.update()}static mainHeadingRequired(e,i){return new Issue("mainHeadingRequired",e,i)}static mainHeadingNumber(e,i){return new Issue("mainHeadingNumber",e,i)}static mainHeadingPosition(e,i){return new Issue("mainHeadingPosition",e,i)}static headingStructure(e,i){return new Issue("headingStructure",e,i)}}export{Issue};