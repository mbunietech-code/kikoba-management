<textarea rows="{{ $attributes->get('rows', 3) }}" {{ $attributes->except('rows')->class('k-input') }}>{{ $slot }}</textarea>
