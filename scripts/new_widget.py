#!/usr/bin/env python3
"""Scaffold a new Lyra widget and register it in the manifest.

Usage:
  python scripts/new_widget.py --name feature-card --title "Feature Card" [--group general]

Creates lyra-core/widgets/<group>/class-lyra-<name>.php (extends Lyra_Base_Widget)
and adds one line to Lyra_Elementor::$manifest. Afterwards: fill in the controls
(defaults = text from the design), write render(), add scoped CSS to
assets/css/components.css, then run scripts/lint_plugin.py.
"""
import argparse
import os
import re
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PLUGIN = os.path.join(ROOT, "lyra-core")
ELEMENTOR_CLASS = os.path.join(PLUGIN, "includes", "class-lyra-elementor.php")

TEMPLATE = """<?php
/**
 * {{TITLE}} widget.
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class {{CLASS}} extends Lyra_Base_Widget {

	public function get_name() {
		return '{{NAME}}';
	}

	public function get_title() {
		return __( '{{TITLE}}', 'lyra-core' );
	}

	public function get_icon() {
		return 'eicon-code';
	}

	public function get_keywords() {
		return array( 'lyra', '{{SLUG}}' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content',
			array( 'label' => __( 'Content', 'lyra-core' ) )
		);

		// Every text, icon, image, link and repeating item must be a control,
		// with the design's text as the default value.
		$this->add_control(
			'title',
			array(
				'label'   => __( 'Title', 'lyra-core' ),
				'type'    => \\Elementor\\Controls_Manager::TEXT,
				'default' => __( '{{TITLE}}', 'lyra-core' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->end_controls_section();

		$this->add_text_style( 'title', __( 'Title', 'lyra-core' ), '.{{BEM}}__title' );
	}

	protected function render() {
		echo '<div class="lyra-widget {{BEM}}">';
		if ( $this->s( 'title' ) ) {
			echo '<h3 class="{{BEM}}__title">' . esc_html( $this->s( 'title' ) ) . '</h3>';
		}
		echo '</div>';
	}
}
"""


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--name", required=True, help="kebab-case, without the lyra- prefix")
    ap.add_argument("--title", required=True)
    ap.add_argument("--group", default="general")
    a = ap.parse_args()

    if not re.fullmatch(r"[a-z][a-z0-9-]*", a.name):
        sys.exit("--name must be kebab-case: letters, digits, dashes")
    if not re.fullmatch(r"[a-z][a-z0-9-]*", a.group):
        sys.exit("--group must be kebab-case")

    cls = "Lyra_" + "_".join(p.capitalize() for p in a.name.split("-"))
    rel = "%s/class-lyra-%s.php" % (a.group, a.name)
    path = os.path.join(PLUGIN, "widgets", rel)
    if os.path.exists(path):
        sys.exit("already exists: " + path)

    code = (TEMPLATE.replace("{{CLASS}}", cls)
            .replace("{{NAME}}", "lyra-" + a.name)
            .replace("{{SLUG}}", a.name)
            .replace("{{BEM}}", "lyra-" + a.name)
            .replace("{{TITLE}}", a.title.replace("'", "")))

    os.makedirs(os.path.dirname(path), exist_ok=True)
    idx = os.path.join(os.path.dirname(path), "index.php")
    if not os.path.exists(idx):
        with open(idx, "w", encoding="utf-8") as f:
            f.write("<?php\n// Silence is golden.\n")
    with open(path, "w", encoding="utf-8", newline="\n") as f:
        f.write(code)

    with open(ELEMENTOR_CLASS, encoding="utf-8") as f:
        src = f.read()
    if "'%s'" % cls in src:
        sys.exit("manifest already contains " + cls)
    m = re.search(r"(public static \$manifest = array\(\n)", src)
    if not m:
        sys.exit("could not find $manifest in class-lyra-elementor.php")
    line = "\t\t'%s' => '%s',\n" % (cls, rel)
    src = src[:m.end()] + line + src[m.end():]
    with open(ELEMENTOR_CLASS, "w", encoding="utf-8", newline="\n") as f:
        f.write(src)

    print("created  lyra-core/widgets/" + rel)
    print("manifest +", cls)
    print("next: controls + render(), CSS in lyra-core/assets/css/components.css (scope: .lyra-%s), then lint" % a.name)


if __name__ == "__main__":
    main()
