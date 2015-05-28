# Matisse

## What is Matisse?

Matisse is a component-based template engine for PHP web applications.

Matisse generates an HTML document by combining a source (template) document with data fetched from your domain model.

The source template is a text file with XHTML markup where, besides common HTML tags, components can be specified using special tags prefixed with `c:` for components or `p:` for component parameters.

Example of a Matisse template:

```HTML
<h1>Some HTML text</h1>
<form>
	<Input name="field1" value="{$myVar}"/>
	<ul>
	<Repeater data="{$myData}">
		<li>Item {$name}</li>
		<NoData>The are no items.</p:no-data>
	</Repeater>
</ul>
```

Each component tag is converted into an instance of a corresponding PHP class. When the template is rendered, each component instance is responsible for generating an HTML representation of that component, together with optional (embedded or external) javascript code and stylesheet references or embedded CSS styles.

Components can also be defined with pure markup via template files, without any PHP code. Those templates are conceptually similar to parametric macros.

> Templated components are implemented by a generic `TemplateInstance` class that loads the template from an external file.

An example of a component template that implements a customizable panel:

```HTML
<Template name="Form">
  <Param name="type" type="text" default="box-solid box-default"/>
  <Param name="title" type="text"/>
  <Param name="content" type="source"/>
  <Param name="actions" type="source"/>

  <div class="form box {@type}">
    <If the="{@title}" is-set>
      <div class="box-header with-border">
        <h3 class="box-title">{@title}</h3>
      </div>
    </If>
    <div class="box-body">
      {@content}
    </div>
    <div class="box-footer">
      <ButtonBar>
        {@actions}
      </ButtonBar>
    </div>
  </div>
</Template>
```

You can then create instances of this component like this:

```HTML
<Form type="box-info" title="My title">
  <h1>Welcome</h1>
  <p>Some text here...</p>
    <Footer>
      Some footer markup here...
    </Footer>
  </Form>
```


> XHTML documents are very similar to HTML documents but all tags must be closed.  
> For instance, `<br>` must be written as `<br/>` and an empty `div` can be written as `<div/>`.  
> Both examples are not valid HTML syntax, but they are valid XML syntax.

> Note: Component tags always begin with a capital letter and are camel cased. Regular HTML tags must be always lower cased.

Templates, when run for the first time, are parsed into a component tree which is then saved in serialized form into a cache file. Further template renderings will fetch the pre-parsed version from the cache file (or shared memory buffer), therefore speeding up the rendering process.

When a source template file is modified and subsequently requested, Matisse will automatically re-parse and re-cache the updated version.

