define(() => {
  const translate = key => TYPO3.lang[key] || key;

  return translate;
});
