<ul @if($root) id="tree-checkbox" class="tree-checkbox treeview" @endif>
    @foreach($items as $name => $value)
        <li>
            <strong>{{ html_entity_decode((string) $name, ENT_QUOTES | ENT_HTML5, 'UTF-8') }} :</strong>
            @if(is_array($value))
                @include('admin.pages.partials.phpinfo_tree', ['items' => $value, 'root' => false])
            @else
                {{ html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8') }}
            @endif
        </li>
    @endforeach
</ul>
