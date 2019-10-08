# The Semantilizer

## :question: What is it?

The Semantilizer is a TYPO3 extension, that adds more functionality to the TYPO3 own headlines of content elements. This extension will detach the semantic definition from the field `header_layout` for the headlines of the content elements. It also adds an overview over all currently used headlines in content elements across the current page. This will display also potential errors in the structuring of headlines and gives easy fixing options.

## :wrench: Installation

* Get the extension via composer: `composer require zeroseven/z7-semantilizer`
* Include the typoscript setup **after** the configuration of fluid_styled_content to override their partials for the headlines

## :roller_coaster: How to use

Now, if you want, you can simply overwrite the labels of the `header_layouts` to make them more understandable.

**Example:**

```tsconfig
TCEFORM.tt_content {
    header_layout {
      removeItems = 2
      altLabels.. = Medium
      altLabels.1 = Larger
      altLabels.3 = Smaller
      addItems.fancy_pink_sparkling_turned_around_bouncing_header = The nice one!
    }
}
```


## Options 

You can disable the preview of the headlines on some pages. Just configure the following line in your TSconfig;

```
tx_semantilizer.disableOnPages = 42,84
```

## Todo's

* There is currently no support or concept for multi language pages
