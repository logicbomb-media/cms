<li id="itemOrder_<?= $item->id ?>" class="js-menu-item">
    <div>
        @if(count($item->items))
            <button type="button" class="dvs-button dvs-button-small menu-accordion dvs-pl mr sp5" data-target="#children-<?= $item->id ?>"><span class="ion-android-expand"></span></button>
        @endif

        <input type="text" name="item[<?=$item->id?>][name]" value="<?= $item->name ?>" class="dvs-pl" placeholder="Title">

        @php
            if($item->page) {
                 $urlHiddenClass = ' hidden';
                 $pageHiddenClass = '';
                 $urlSelected = '';
                 $pageSelected = ' selected="selected"';
            } else {
                 $urlHiddenClass = '';
                 $pageHiddenClass = ' hidden';
                 $selected = "url";
                 $urlSelected = ' selected="selected"';
                 $pageSelected = '';
            }
        @endphp

        <select name="item[<?=$item->id?>][url_or_page]" class="url-or-page form-control btn btn-default pull-left">
            <option value="page"<?= $pageSelected ?>>Page</option>
            <option value="url"<?= $urlSelected ?>>URL</option>
        </select>

        <input type="text" name="item[<?= $item->id?>][url]" value="<?= $item->url ?>" placeholder="URL">

        <input type="text" class="autocomplete-pages menu-item-page<?= $pageHiddenClass ?> form-control pull-left" placeholder="Page" value="@if($item->page)<?= $item->page->title ?> (<?= $item->page->language->code  ?>) @endif">
        <input type="hidden" name="item[<?= $item->id  ?>][page_id]" value="<?= (isset($item->page_id)) ? $item->page_id : '' ?>">

        <button type="button" class="dvs-button dvs-button-danger dvs-button-small js-remove-menu-item dvs-pr"><span class="ion-android-close"></span></button>
    </div>

	@if ($item->items)
		<ol>
			@foreach ($item->items as $nested)
				@include('devise::admin.menus._items', ['item' => $nested])
			@endforeach
		</ol>
	@endif
</li>