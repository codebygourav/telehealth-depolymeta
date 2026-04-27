const LoadingSkeleton = () => {
  return (
    <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 animate-pulse">
      <div className="lg:col-span-8 space-y-8">
        {/* Header Skeleton */}
        <div className="bg-surface-container-lowest rounded-3xl p-8">
          <div className="flex flex-col md:flex-row gap-8 items-center">
            <div className="h-40 w-40 rounded-3xl bg-gray-200" />
            <div className="flex-1 space-y-4">
              <div className="h-8 w-64 bg-gray-200 rounded" />
              <div className="h-6 w-48 bg-gray-200 rounded" />
              <div className="h-4 w-32 bg-gray-200 rounded" />
            </div>
          </div>
        </div>
        
        {/* Tabs Skeleton */}
        <div className="flex gap-8 border-b">
          <div className="h-12 w-24 bg-gray-200 rounded" />
          <div className="h-12 w-24 bg-gray-200 rounded" />
        </div>
        
        {/* Content Skeleton */}
        <div className="h-64 bg-gray-200 rounded-3xl" />
      </div>
      
      {/* Booking Skeleton */}
      <div className="lg:col-span-4">
        <div className="h-[600px] bg-gray-200 rounded-3xl" />
      </div>
    </div>
  );
};

export default LoadingSkeleton;