<?php 
/**
 * Helper for bigbigbluebutton recordings.
 * 
 * @since   3.0.0
 */
class BigbluebuttonRecordingHelper {

    private $recordings;

    /**
	 * Initialize the class.
	 *
	 * @since   3.0.0
	 */
	public function __construct() {
        $this->recordings = array();
    }

    /**
     * Get filtered and ordered recordings for the room based on capability.
     * 
     * @param   Integer $room_id        Room ID for the list of recordings.
     * @return  Array   $recordings     List of filtered and sorted recordings.
     */
    public function get_filtered_and_ordered_recordings_based_on_capability($room_id, $order = '', $orderby = '') {
        $this->get_recordings_based_on_capability($room_id);
        $this->filter_recordings();
        $this->order_recordings($order, $orderby);
        return $this->recordings;
    }
    
    /**
     * Get recordings for the room based on capability.
     * 
     * @since   3.0.0
     * 
     * @return  SimpleXMLObject     $recordings     List of recordings.
     */
    public function get_recordings_based_on_capability($room_id) {
        $manage_recordings = current_user_can('manage_bbb_room_recordings');
        if ($manage_recordings) {
            $this->recordings = BigbluebuttonApi::get_recordings($room_id, 'published,unpublished');
        } else {
            $this->recordings = BigbluebuttonApi::get_recordings($room_id, 'published');
        }
        return $this->recordings;
    }

    /**
	 * Filter recordings based on whether the user can manage them or not.
	 * 
	 * Assign icon classes and title based on recording published and protected status.
	 * If the user cannot manage recordings, hide them.
	 * Get recording name and description from metadata.
	 * 
	 * @since	3.0.0
	 */
	private function filter_recordings() {
        $manage_recordings = current_user_can('manage_bbb_room_recordings');
		$filtered_recordings = array();
		foreach ($this->recordings as $recording) {
			// set recording name to be meeting name if recording name is not yet set
			if ( ! isset($recording->metadata->{'recording-name'})) {
				$recording->metadata->{'recording-name'} = $recording->name;
			}
			// set recording description to be an empty string if it is not yet set
			if ( ! isset($recording->metadata->{'recording-description'})) {
				$recording->metadata->{'recording-description'} = "";
			}
			if ($manage_recordings) {
				$recording = $this->filter_managed_recording($recording);
				array_push($filtered_recordings, $recording);
			} else if ($recording->published == 'true') {
				array_push($filtered_recordings, $recording);
			}
        }
        $this->recordings = $filtered_recordings;
    }
    
    /**
	 * Assign classes and title for the icon based on the recording's publish and protect status.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	SimpleXMLElement	$recording	A recording to be inspected.
	 * @return	SimpleXMLElement	$recording	A recording that has been inspected.
	 */
	private function filter_managed_recording($recording) {
		if ($recording->protected == 'true') {
			$recording->protected_icon_classes = "fa fa-lock fa-icon bbb-icon bbb_protected_recording is_protected";
			$recording->protected_icon_title = __('Protected', 'bigbluebutton');
		} else if ($recording->protected == 'false') {
			$recording->protected_icon_classes = "fa fa-unlock fa-icon bbb-icon bbb_protected_recording not_protected";
			$recording->protected_icon_title = __('Unprotected', 'bigbluebutton');
		}

		if ($recording->published == 'true') {
			$recording->published_icon_classes = "fa fa-eye fa-icon bbb-icon bbb_published_recording is_published";
			$recording->published_icon_title = __('Published');
		} else {
			$recording->published_icon_classes = "fa fa-eye-slash fa-icon bbb-icon bbb_published_recording not_published";
			$recording->published_icoset_order_by_classesn_title = __('Unpublished');
		}
		return $recording;
    }
    
    /**
	 * Order recordings based on parameters.
	 * 
	 * @since	3.0.0
	 */
	public function order_recordings($order = '', $order_by = '') {
		if ($order != '' && $order_by != '') {
			
			$direction = sanitize_text_field($_GET['order']);
			$field = sanitize_text_field($_GET['orderby']);
			$self = $this;

			usort($this->recordings, function($first, $second) use ($direction, $field, $self) {
				if ($direction == 'asc') {
					return (strcasecmp($self->get_recording_field($first, $field), $self->get_recording_field($second, $field)) > 0);
				} else {
					return (strcasecmp($self->get_recording_field($first, $field), $self->get_recording_field($second, $field)) < 0);
				}
			});

		}
	}

	/**
	 * Get recording field value based on property name.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	SimpleXMLElement	$recording		A recording to get the field name from.
	 * @param	String				$field_name		Name of the field.
	 * 
	 * @return	String				$field_value	Value of the field.	
	 */
	private function get_recording_field($recording, $field_name) {
		$field_value = '';
		if ($field_name == 'name') {
			$field_value = strval($recording->metadata->{'recording-name'});
		} else if ($field_name == 'description') {
			$field_value = strval($recording->metadata->{'recording-description'});
		} else if ($field_name == 'date') {
			$field_value = strval($recording->startTime);
		}
		return $field_value;
	}
}
