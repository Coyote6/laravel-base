<?php


namespace Coyote6\LaravelBase\Traits\Models\Boot;


trait OriginalAuthor {


	// Create Original Author
	//
	// Sets original_author_id to the current user; independent of Author/author_id.
	//
	// @ai
	//		This previously mirrored author_id directly (original_author_id =
	//		$this->author_id), which always overwrote any value already present.
	//		That broke the same bulk-fill guard Author::createAuthor() relies on
	//		to avoid clobbering an explicitly-provided author_id during imports,
	//		where the imported content's real author can differ from the admin
	//		running the import. Deriving original_author_id independently from
	//		the current user, behind the same is_null/empty guard createAuthor()
	//		uses, lets it respect a bulk-filled value the same way author_id
	//		already does -- and means this trait no longer requires Author or
	//		the author_id field to be present at all.
	//
	// @return void
	//
	public function createOriginalAuthor () {
		$field = config('coyote6-base.original_author.field', 'original_author_id');

		if (is_null ($this->{$field}) || $this->{$field} == '') {
			$userId = getCurrentUserId();
			if (!is_null ($userId)) {
				$this->{$field} = $userId;
			}
		}
	}


}
