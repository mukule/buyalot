import Swal from 'sweetalert2';

export const useConfirm = () => {
    const confirmDelete = async (options: {
        title?: string;
        text?: string;
        confirmButtonText?: string;
        cancelButtonText?: string;
    } = {}) => {
        const result = await Swal.fire({
            title: options.title || 'Are you sure?',
            text: options.text || "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444', // Tailwind red-500
            cancelButtonColor: '#6b7280',  // Tailwind gray-500
            confirmButtonText: options.confirmButtonText || 'Yes, delete it!',
            cancelButtonText: options.cancelButtonText || 'Cancel',
            reverseButtons: true,
            customClass: {
                confirmButton: 'rounded-lg px-4 py-2 bg-red-600 text-white font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
                cancelButton: 'rounded-lg px-4 py-2 bg-gray-500 text-white font-medium hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 mr-3'
            },
            buttonsStyling: false
        });

        return result.isConfirmed;
    };

    const confirmAction = async (options: {
        title: string;
        text: string;
        icon?: 'warning' | 'error' | 'success' | 'info' | 'question';
        confirmButtonText?: string;
        confirmButtonColor?: string;
    }) => {
        const result = await Swal.fire({
            title: options.title,
            text: options.text,
            icon: options.icon || 'question',
            showCancelButton: true,
            confirmButtonColor: options.confirmButtonColor || '#3b82f6', // Tailwind blue-500
            cancelButtonColor: '#6b7280',
            confirmButtonText: options.confirmButtonText || 'Confirm',
            reverseButtons: true,
            customClass: {
                confirmButton: 'rounded-lg px-4 py-2 bg-blue-600 text-white font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2',
                cancelButton: 'rounded-lg px-4 py-2 bg-gray-500 text-white font-medium hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 mr-3'
            },
            buttonsStyling: false
        });

        return result.isConfirmed;
    };

    return {
        confirmDelete,
        confirmAction
    };
};
