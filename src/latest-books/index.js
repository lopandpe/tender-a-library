import { registerBlockType } from "@wordpress/blocks";

import "./style.scss";
import "./editor.scss";

import Edit from "./edit";

registerBlockType("tender-a-library/latest-books", {
	edit: Edit,
	save: () => null,
});
