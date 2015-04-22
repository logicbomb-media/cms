<h3>Wysiwyg</h3>

<p style="font-style: italic;">Adding images insdie of wysiwyg not currently working</p>

<div data-devise="wysiwyg1, wysiwyg, Wysiwyg">
	<?= $page->wysiwyg1->text ?>
</div>

<pre class="devise-code-snippet"><code class="html">
<?= htmlentities('
<div data-devise="wysiwyg1, wysiwyg, Wysiwyg">
	{{ $page->wysiwyg1->text }}
</div>
') ?>
</code></pre>

@include('devise::admin.fields.show',
[
    'name' => '$page->wysiwyg1',
    'values' => $page->wysiwyg1,
    'descriptions' => [
    	'text' => 'HTML content delivered by WYSIWYG editor'
    ],
])
