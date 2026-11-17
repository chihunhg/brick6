// !=======手風琴效果 - 無障礙2A等級
document.addEventListener("DOMContentLoaded", function() {
	// 取得所有手風琴群組
	var accordionGroups = document.querySelectorAll(".accordionGp");

	// 遍歷每個手風琴群組
	accordionGroups.forEach(function(accParent) {
		var accordionHeaders = accParent.getElementsByClassName("accordionBx__hd");
		var i;

		function panelHasEditor(panel) {
			return !!(panel && panel.querySelector("textarea.ckeditor, .jodit-container"));
		}

		function setPanelOpenHeight(panel) {
			if (!panel) {
				return;
			}
			if (panelHasEditor(panel)) {
				panel.style.setProperty("max-height", "none", "important");
				return;
			}
			panel.style.removeProperty("max-height");
			panel.style.maxHeight = panel.scrollHeight + "px";
		}

		function setPanelClosedHeight(panel) {
			if (!panel) {
				return;
			}
			panel.style.removeProperty("max-height");
		}

		// 預設每個群組的第一個手風琴+父層不包含--notOpen時為開啟狀態
		if (accordionHeaders.length > 0 && !accParent.classList.contains("--notOpen")) {
			var firstHeader = accordionHeaders[0];
			var firstPanel = firstHeader.nextElementSibling;

			// 設定初始狀態
			firstHeader.classList.add("active");
			firstHeader.setAttribute("aria-expanded", "true");
			firstPanel.setAttribute("aria-hidden", "false");
			setPanelOpenHeight(firstPanel);

			// 啟用第一個面板的聚焦
			enablePanelFocus(firstPanel);
		}

		// 初始化所有面板的聚焦狀態
		for (i = 0; i < accordionHeaders.length; i++) {
			var header = accordionHeaders[i];
			var panel = header.nextElementSibling;
			var isExpanded = header.getAttribute("aria-expanded") === "true";

			if (!isExpanded) {
				// 收合狀態的面板：禁用聚焦
				disablePanelFocus(panel);
			}
		}

		// 為每個手風琴標題設定事件
		for (i = 0; i < accordionHeaders.length; i++) {
			var header = accordionHeaders[i];
			var panel = header.nextElementSibling;

			// 點擊事件
			header.addEventListener("click", function() {
				toggleAccordion(this);
			});

			// 鍵盤事件
			header.addEventListener("keydown", function(e) {
				var key = e.key;
				var currentIndex = Array.from(accordionHeaders).indexOf(this);

				switch (key) {
					case "Enter":
					case " ":
						e.preventDefault();
						toggleAccordion(this);
						break;

					case "ArrowDown":
						e.preventDefault();
						var nextIndex = (currentIndex + 1) % accordionHeaders.length;
						accordionHeaders[nextIndex].focus();
						break;

					case "ArrowUp":
						e.preventDefault();
						var prevIndex = currentIndex === 0 ? accordionHeaders.length - 1 : currentIndex - 1;
						accordionHeaders[prevIndex].focus();
						break;

					case "Home":
						e.preventDefault();
						accordionHeaders[0].focus();
						break;

					case "End":
						e.preventDefault();
						accordionHeaders[accordionHeaders.length - 1].focus();
						break;
				}
			});

			// 焦點事件 - 添加視覺指示器
			header.addEventListener("focus", function() {
				this.classList.add("focus");
			});

			header.addEventListener("blur", function() {
				this.classList.remove("focus");
			});
		}

		// 手風琴切換函數
		function toggleAccordion(header) {
			var panel = header.nextElementSibling;
			var isExpanded = header.getAttribute("aria-expanded") === "true";

			// 切換狀態
			header.classList.toggle("active");
			header.setAttribute("aria-expanded", !isExpanded);
			panel.setAttribute("aria-hidden", isExpanded);

			// 管理面板內元素的聚焦狀態
			if (isExpanded) {
				// 收合時：禁用面板內所有可聚焦元素
				disablePanelFocus(panel);
				setPanelClosedHeight(panel);
			} else {
				// 展開時：啟用面板內所有可聚焦元素
				enablePanelFocus(panel);
				setPanelOpenHeight(panel);
			}
		}

		// 禁用面板內元素的聚焦
		function disablePanelFocus(panel) {
			var focusableElements = panel.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
			focusableElements.forEach(function(element) {
				// 保存原始的 tabindex 值
				var currentTabindex = element.getAttribute('tabindex');
				if (currentTabindex !== '-1') {
					element.setAttribute('data-original-tabindex', currentTabindex);
					element.setAttribute('tabindex', '-1');
				}
			});
		}

		// 啟用面板內元素的聚焦
		function enablePanelFocus(panel) {
			var focusableElements = panel.querySelectorAll('a, button, input, select, textarea, [tabindex="-1"]');
			focusableElements.forEach(function(element) {
				// 恢復原本的 tabindex 值
				var originalTabindex = element.getAttribute('data-original-tabindex');
				if (originalTabindex !== null) {
					element.setAttribute('tabindex', originalTabindex);
					element.removeAttribute('data-original-tabindex');
				} else {
					// 如果沒有原始值，根據元素類型設定預設值
					if (element.tagName === 'A' || element.tagName === 'BUTTON') {
						element.setAttribute('tabindex', '0');
					} else {
						element.removeAttribute('tabindex');
					}
				}
			});
		}

		// 處理手風琴面板內 details 展開時的高度問題
		var accordionPanels = accParent.querySelectorAll(".accordionBx__panel");
		accordionPanels.forEach(function(panel) {
			var detailsElements = panel.querySelectorAll("details");
			
			detailsElements.forEach(function(details) {
				// 監聽 details 的 toggle 事件
				details.addEventListener("toggle", function() {
					// 如果手風琴面板是展開狀態（有 maxHeight），重新計算高度
					if (panel.style.maxHeight && panel.style.maxHeight !== "0px") {
						// 使用 setTimeout 確保 details 內容已完全展開/收合
						setTimeout(function() {
							panel.style.maxHeight = panel.scrollHeight + "px";
						}, 10);
					}
				});
			});
		});
	});
});