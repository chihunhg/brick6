CKEDITOR.editorConfig = function( config ) {
	config.toolbarGroups = [
		{ name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
		{ name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
		{ name: 'editing', groups: [ 'selection', 'editing' ] },
		{ name: 'links', groups: [ 'links' ] },
		{ name: 'insert', groups: [ 'insert' ] },
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		'/',
		{ name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'paragraph' ] },
		{ name: 'styles', groups: [ 'styles' ] },
		{ name: 'colors', groups: [ 'colors' ] }
	];

	config.extraPlugins = 'youtube';
	config.youtube_width = '640';
	config.youtube_height = '480';
	config.youtube_responsive = true;
	config.youtube_older = false;
	config.youtube_related = true;
	config.youtube_autoplay = false;
	config.youtube_controls = true;
	config.youtube_privacy = false;
	config.removeButtons = 'Save,NewPage,Preview,Print,Templates,Find,SelectAll,Scayt,Form,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,CreateDiv,Language,CopyFormatting,Flash,Smiley,PageBreak,Iframe,ShowBlocks,About,Replace,Checkbox,BidiLtr,BidiRtl';
	config.removePlugins = 'exportpdf,scayt,wsc,elementspath,forms,flash,iframe,smiley';
	config.pasteFromWordRemoveFontStyles = false;
	config.pasteFromWordRemoveStyles = false;
	config.versionCheck = false;
	config.resize_enabled = true;

	config.format_tags = 'p;h1;h2;h3;pre';
	config.allowedContent = true;

	/**
	 * 編輯區樣式：bootstrap 先載，contents.css 後載才能覆寫字級
	 * 路徑一律相對 CKEDITOR.basePath（manage/ckeditor/），避免各模組目錄下相對路徑失效
	 */
	config.contentsCss = [
		CKEDITOR.basePath + '../js/bootstrap/bootstrap.min.css',
		CKEDITOR.basePath + 'contents.css'
	];

	CKEDITOR.dtd.$removeEmpty.i = 0;
	CKEDITOR.dtd.$removeEmpty.span = 0;
	
	config.font_names = "新細明體;標楷體;微軟正黑體;Arial;Courier New;Georgia;Verdana;Tahoma;Times New Roman;" ;
	// 編輯區預設字級（對應工具列「大小」16，與後台 body 約 1.0625rem 接近）
	config.fontSize_defaultLabel = '16';								
	
	// elFinder 路徑維持您的設定
	config.filebrowserBrowseUrl = '../elFinder/elfinder_cke.html';
	config.filebrowserImageBrowseUrl = '../elFinder/elfinder_cke.html?Type=Images';

	config.width = '99%';
	config.height = '200px';
	config.skin = 'moono-lisa';
};
