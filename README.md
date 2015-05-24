# Matisse

## What is Matisse?

Matisse is a component-based template engine for PHP web applications.

Matisse generates an HTML document by combining a source (template) document with data fetched from your domain model.

The source template is a text file with XHTML markup where, besides common HTML tags, components can be specified using special tags prefixed with `c:` for components or `p:` for component parameters.

Example of a Matisse template:

```HTML
<h1>Some HTML text</h1>
<form>
	<c:input name="field1" value="{$myVar}"/>
	<ul>
	<c:repeater data="{$myData}">
		<li>Item {$name}</li>
		<p:no-data>The are no items.</p:no-data>
	</c:repeater>
</ul>
```

Each component tag is converted into an instance of a corresponding PHP class. When the template is rendered, each component instance is responsible for generating an HTML representation of that component, together with optional (embedded or external) javascript code and stylesheet references or embedded CSS styles.

Components can also be defined with pure markup via template files, without any PHP code. Those templates are conceptually similar to parametric macros.

> Templated components are implemented by a generic `TemplateInstance` class that loads the template from an external file.

An example of a component template that implements a customizable panel:

```HTML
<c:template name="form">
  <p:param name="type" type="text" default="box-solid box-default"/>
  <p:param name="title" type="text"/>
  <p:param name="content" type="source"/>
  <p:param name="actions" type="source"/>
  <p:body>
    <div class="form box {@type}">
      <c:if the="{@title}" is-set>
        <div class="box-header with-border">
          <h3 class="box-title">{@title}</h3>
        </div>
      </c:test>
      <div class="box-body">
        {@content}
      </div>
      <div class="box-footer">
        <div class="buttonBar right">
          {@actions}
        </div>
      </div>
    </div>
  </p:body>
</c:template>
```

You can then create instances of this component like this:

```HTML
<c:form type="box-info" title="My title">
	<p:content>
<h1>Welcome</h1>
<p>Some text here...</p>
	</p:content>
	<p:footer>
		Some footer markup here...
	</p:footer>
</c:form>
```


> XHTML documents are very similar to HTML documents but all tags must be closed.  
> For instance, `<br>` must be written as `<br/>` and an empty `div` can be written as `<div/>`.  
> Both examples are not valid HTML syntax, but they are valid XML syntax.

> Note: although the `c:` and `p:` prefixes are similar to XML namespace prefixes, no real namespaces are used, nor should you define any XML namespace on templates. These prefixes are used only to differentiate component markup from normal XHTML markup.

Templates, when run for the first time, are parsed into a component tree which is then saved in serialized form into a cache file. Further template renderings will fetch the pre-parsed version from the cache file (or shared memory buffer), therefore speeding up the rendering process.

When a source template file is modified and subsequently requested, Matisse will automatically re-parse and re-cache the updated version.

