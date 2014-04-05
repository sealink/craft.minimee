<?php
namespace Craft;

interface Minimee_AssetModelInterface
{
	public function __toString();

	public function defineAttributes();

	public function getContents();

	public function getLastTimeModified();

	public function exists();
}