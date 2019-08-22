# The semantilizer

This extension will detach the semantic definition from the field `header_layout` for the headlines of the content elements. Further you will get a semantic overview of your headline structure in the backend which also validate some mistakes in your headline structure.

## Installation

Install the extension and include the typoscript setup **after** the configuration of `fluid_styled_content` to override their partials for the headlines.

## How to use

Now, if you want, you can simply override the labels of the `header_layouts` to make them more understandable.

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

* Translate the locallang files
* There is currently no support or concept for multi language pages
